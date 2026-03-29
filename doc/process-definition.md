# Defining Processes (YAML)

[back](./README.md)

## File Location

Process YAML files are placed in `config/process/` (configurable). Each file can define one or more processes under the `spipu_process` root key.

## Minimal Example

```yaml
# config/process/hello.yaml
spipu_process:
    hello_world:
        name: "Hello World"
        options:
            can_be_put_in_queue: false
            can_be_rerun_automatically: false
        steps:
            say_hello:
                class: Spipu\ProcessBundle\Step\Test\HelloWorld
```

## Full YAML Structure

```yaml
spipu_process:
    my_process:
        name: "Human-readable process name"

        options:
            can_be_put_in_queue: true          # Can be queued for async execution (required)
            can_be_rerun_automatically: false   # Eligible for auto-retry on failure (required)
            process_lock: []                    # List of process codes that must not run concurrently (default: [])
            process_lock_on_failed: true        # Keep lock if process fails (default: true)
            needed_role: ~                      # Optional role required to execute this process (default: null)
            automatic_report: false             # Send an automatic report email after execution (default: false)

        inputs:
            my_string_param:
                type: string
                required: true
                regexp: '/^[a-z]+$/'
                help: "Only lowercase letters"

            my_int_param:
                type: int
                required: false

        parameters:
            my_static_value: "hello"
            my_static_array:
                - "first"
                - "second"

        steps:
            step_one:
                class: App\Step\MyFirstStep
                parameters:
                    source: "{{ input.my_string_param }}"   # Input value
                    count:  "{{ input.my_int_param }}"

            step_two:
                class: App\Step\MySecondStep
                ignore_in_progress: false                   # Exclude this step from progress calculation (default: false)
                parameters:
                    data: "{{ result.step_one }}"           # Return value of step_one
                    prefix: "processed_{{ input.my_string_param }}"
```

## Input Types

| Type | PHP type | Notes |
|------|----------|-------|
| `string` | `string` | Plain text |
| `int` | `int` | Integer number |
| `float` | `float` | Floating-point number |
| `bool` | `bool` | Boolean |
| `array` | `array` | Array of values |
| `file` | `string` | Path to an uploaded/local file; combine with `allowed_mime_types` |
| `date` | `string` | Validated against `YYYY-MM-DD` format |
| `datetime` | `string` | Validated against `YYYY-MM-DD HH:MM:SS` format |

Input definitions accept the following keys:

| Key | Required | Description |
|-----|----------|-------------|
| `type` | yes | One of the types above. Can also be given as a bare string shorthand (e.g. `my_input: "string"`) |
| `required` | no | Default `true`. If `false`, the input may be omitted |
| `options` | no | FQCN of an `AbstractOptions` class; restricts allowed values to those listed |
| `allowed_mime_types` | no | Only valid with `type: file`. One or more MIME types |
| `regexp` | no | Only valid with `type: string`. PHP regexp (e.g. `'/^[A-Z]{2}$/'`) |
| `help` | no | Help text shown in the admin UI |

## Process-Level Parameters

The `parameters` section defines static values available to all steps in the process. Unlike inputs, these are fixed in the YAML and cannot be overridden at runtime.

```yaml
parameters:
    base_url: "https://api.example.com/"
    batch_size: 500
```

## Parameter Interpolation

Step `parameters` values support a `{{ variable }}` syntax to reference dynamic values.

- **Full replacement** — `"{{ key }}"` alone: returns the actual typed value (int, array, object, etc.)
- **Partial replacement** — `"prefix_{{ key }}_suffix"`: converts to string and concatenates

### Available variables in a step's `parameters`

| Variable | Value |
|----------|-------|
| `{{ input.<name> }}` | Value of a named process input |
| `{{ result.<step_code> }}` | Return value of a completed step |
| `{{ time.<step_code> }}` | Execution time in seconds of a completed step |
| `{{ <param_name> }}` | A process-level `parameters` value |
| `{{ configuration(<key>) }}` | Value from the ConfigurationBundle (e.g. `{{ configuration(process.archive.keep_number) }}`) |
| `{{ loop.key }}` | *(inside LoopStep only)* Current iteration key |
| `{{ loop.value }}` | *(inside LoopStep only)* Current iteration value |
| `{{ loop.result.<step_code> }}` | *(inside LoopStep only)* Step result from this iteration |
| `{{ loop.result }}` | *(inside LoopStep only)* Result of the last step in the previous iteration |

## Options Reference

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `can_be_put_in_queue` | bool | — (required) | Allow async/queued execution; also required when `can_be_rerun_automatically` or `automatic_report` is `true` |
| `can_be_rerun_automatically` | bool | — (required) | Re-run automatically on failure (controlled by module config); requires `can_be_put_in_queue: true` |
| `process_lock` | string\|array | `[]` | List of process codes; only one instance of any listed code may run at a time |
| `process_lock_on_failed` | bool | `true` | Keep the lock even if the process fails |
| `needed_role` | string\|null | `null` | Role required to see/execute this process in the admin UI |
| `automatic_report` | bool | `false` | Automatically send an email report after execution (requires `can_be_put_in_queue: true`); adds a mandatory `report_email` input |

## LoopStep Example

The `LoopStep` iterates over a collection (`iterable` parameter) and runs a sub-sequence of steps for each element.

```yaml
steps:
    process_items:
        class: Spipu\ProcessBundle\Step\LoopStep
        parameters:
            iterable: "{{ result.some_step }}"   # Must be an array or countable iterable
            steps:
                log_item:
                    class: App\Step\LogItem
                    parameters:
                        key:   "{{ loop.key }}"
                        value: "{{ loop.value }}"
                transform_item:
                    class: App\Step\TransformItem
                    parameters:
                        input:      "{{ loop.value }}"
                        log_result: "{{ loop.result.log_item }}"
```

[back](./README.md)
