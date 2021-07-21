<?php
declare(strict_types=1);

namespace Spipu\ProcessBundle\Step\Generic;

use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Exception\CallRestException;
use Spipu\ProcessBundle\Exception\StepException;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;

/**
 * Class CallRest
 *
 * @package Spipu\ProcessBundle\Step\Generic
 */
class CallRest implements StepInterface
{
    /**
     * @var array
     */
    private $headers;

    /**
     * the HTTP return status
     * @var array
     */
    protected $status;

    /**
     * @param ParametersInterface $parameters
     * @param LoggerInterface $logger
     * @return mixed
     * @throws CallRestException
     * @throws StepException
     */
    public function execute(ParametersInterface $parameters, LoggerInterface $logger)
    {
        $url     = $parameters->get('url');
        $method  = $this->getMethod($parameters);
        $options = $this->getOptions($parameters);
        $data    = $this->getQueryString($method, $parameters);

        if ($data !== '') {
            $options['headers'][] = 'Content-length: ' . strlen($data);
        }

        $this->init();
        $curl = $this->getCurlSession($url, $options);
        $this->applyMethodAndData($curl, $method, $data);

        $logger->debug(
            sprintf(
                'CURL [%s] %s',
                $method,
                $url
            )
        );

        $result = curl_exec($curl);
        $error = null;
        if (curl_errno($curl)) {
            $error = [
                'code'    => (int) curl_errno($curl),
                'message' => curl_error($curl),
            ];
        }
        if ($this->status['code'] == 404) {
            $error = [
                'code'    => (int) $this->status['code'],
                'message' => 'Not Found',
            ];
        }
        curl_close($curl);

        if (!is_null($error)) {
            throw new CallRestException($error['message'], $error['code']);
        }

        return [
            'status'  => $this->status,
            'headers' => $this->headers,
            'content' => $result
        ];
    }

    /**
     * Init the processor
     * @return void
     */
    private function init(): void
    {
        $this->headers = [];
        $this->status = ['code' => 0, 'message' => 'not executed'];
    }

    /**
     * @param ParametersInterface $parameters
     * @return array
     * @throws StepException
     */
    private function getOptions(ParametersInterface $parameters): array
    {
        $options = $parameters->get('options');
        if (!is_array($options)) {
            throw new StepException('The options must be an array');
        }

        if (!array_key_exists('headers', $options)) {
            $options['headers'] = [];
        }

        if (!array_key_exists('curl_opt', $options)) {
            $options['curl_opt'] = [];
        }

        if (!is_array($options['headers'])) {
            throw new StepException('The headers part must be an array');
        }

        if (!is_array($options['curl_opt'])) {
            throw new StepException('The curl_opt part must be an array');
        }

        if (array_key_exists('timeout', $options)) {
            $options['timeout'] = (int) $options['timeout'];
            ini_set('default_socket_timeout', (string) $options['timeout']);
        }

        return $options;
    }

    /**
     * @param ParametersInterface $parameters
     * @return string
     * @throws StepException
     */
    private function getMethod(ParametersInterface $parameters): string
    {
        $method = $parameters->get('method');
        $method = strtoupper($method);

        if (!in_array($method, array('GET', 'POST', 'PUT', 'PATCH', 'DELETE'))) {
            throw new StepException('The method ['.$method.'] is not allowed');
        }

        return $method;
    }

    /**
     * @param string $method
     * @param ParametersInterface $parameters
     * @return string
     * @throws StepException
     */
    private function getQueryString(string $method, ParametersInterface $parameters): string
    {
        if (in_array($method, array('GET', 'DELETE'))) {
            return '';
        }

        $data = $parameters->get('query_string');
        if (!is_string($data)) {
            throw new StepException('The QueryString must be a string');
        }

        if ($data === '') {
            throw new StepException('The QueryString must not be empty');
        }

        return $data;
    }

    /**
     * @param string $url
     * @param array $options
     * @return resource
     */
    private function getCurlSession(string $url, array $options)
    {
        // Init the CURL object.
        $curl = curl_init();

        // Manage HTTP authentication.
        if (!empty($options['login']) && !empty($options['password'])) {
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curl, CURLOPT_USERPWD, $options['login'].':'.$options['password']);
            unset($options['login']);
            unset($options['password']);
        }

        // Timeout.
        if (array_key_exists('timeout', $options)) {
            curl_setopt($curl, CURLOPT_TIMEOUT, $options['timeout']);
        }

        // All the good options.
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, array($this, 'readHeader'));
        curl_setopt($curl, CURLOPT_URL, $url);

        // Add custom headers if asked.
        if (count($options['headers']) > 0) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $options['headers']);
        }

        // Add custom curl options.
        foreach ($options['curl_opt'] as $key => $value) {
            curl_setopt($curl, $key, $value);
        }

        if (array_key_exists('ssl_verify', $options) && $options['ssl_verify'] === 'false') {
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        }

        return $curl;
    }

    /**
     * Apply the method and the data to the curl session
     *
     * @param resource $curl
     * @param string   $method
     * @param string   $data
     *
     * @return void
     */
    private function applyMethodAndData($curl, string $method, string &$data)
    {
        switch ($method) {
            // Read.
            case 'GET':
                curl_setopt($curl, CURLOPT_HTTPGET, 1);
                break;

            // Create.
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;

            // Update.
            case 'PUT':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;

            // Partial Update.
            case 'PATCH':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;

            // Delete.
            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }
    }

    /**
     * read the headers
     *
     * @param resource $resURL
     * @param string   $header
     *
     * @return int
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function readHeader($resURL, string $header)
    {
        // Analyse the headers without status message.
        if (preg_match('/^HTTP\/[0-2].[0-9] ([0-9]+)$/', trim($header), $match)) {
            $this->status = array(
                'code' => $match[1],
                'message' => 'http status '.$match[1],
            );
        }

        // Analyse the headers with status message.
        if (preg_match('/^HTTP\/[0-2].[0-9] ([0-9]+) (.*)$/', trim($header), $match)) {
            $this->status = array(
                'code' => $match[1],
                'message' => $match[2],
            );
        }

        if (trim($header)) {
            array_push($this->headers, trim($header));
        }

        return strlen($header);
    }
}
