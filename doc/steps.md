# Built-in Steps Reference

[back](./README.md)

All built-in steps are in the `Spipu\ProcessBundle\Step\` namespace and are pre-tagged as `spipu.process.step`.

---

## Generic Steps

### `Generic\Sleep`

**Class:** `Spipu\ProcessBundle\Step\Generic\Sleep`

Pauses execution for a given number of seconds.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `seconds` | int | yes | Seconds to sleep |

**Returns:** `true`

---

### `Generic\ExecuteProcess`

**Class:** `Spipu\ProcessBundle\Step\Generic\ExecuteProcess`

Runs another registered process from within the current process. The sub-process is executed synchronously and its log URL is written to the debug log.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `process` | array | yes | Array with keys: `code` (string, process code) and optionally `inputs` (array of input values) |

Example:
```yaml
parameters:
    process:
        code: "my_sub_process"
        inputs:
            some_input: "value"
```

**Returns:** The return value of the last step of the sub-process.

---

### `Generic\BuildResume`

**Class:** `Spipu\ProcessBundle\Step\Generic\BuildResume`

Builds a fixed summary of a standard file-import workflow. It reads specific named parameters from the parameter bag and logs a formatted resume. This step is tightly coupled to the naming conventions of the import workflow steps (`result.get_file`, `result.import_file`, `result.update_database`, `result.archive_file`).

**Returns:** `array` — the resume lines.

---

### `Generic\CallRest`

**Class:** `Spipu\ProcessBundle\Step\Generic\CallRest`

Makes an HTTP request using cURL.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `url` | string | yes | URL to call |
| `method` | string | yes | HTTP method: `GET`, `POST`, `PUT`, `PATCH`, or `DELETE` |
| `query_string` | string | for POST/PUT/PATCH | Request body string (required for non-GET/DELETE methods) |
| `options` | array | yes | cURL options array (see below). Must be present even if empty: `options: []` |

The `options` array supports:

| Key | Type | Description |
|-----|------|-------------|
| `headers` | array | HTTP headers to send (e.g. `["Content-Type: application/json"]`) |
| `timeout` | int | Request timeout in seconds |
| `login` | string | HTTP Basic Auth username |
| `password` | string | HTTP Basic Auth password |
| `ssl_verify` | string | Set to `"false"` to disable SSL certificate verification |
| `curl_opt` | array | Raw cURL option constants and their values |

**Returns:** `array` with keys `status` (array with `code` and `message`), `headers` (array), and `content` (string).

---

## File Steps

### `File\PrepareFilename`

**Class:** `Spipu\ProcessBundle\Step\File\PrepareFilename`

Generates a unique timestamped filename in a given folder. Creates the folder if it does not exist.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `folder` | string | yes | Directory path where the file will be placed |
| `code` | string | yes | Filename prefix/code (e.g. `"import"`) |
| `extension` | string | yes | File extension without dot (e.g. `"csv"`) |

**Returns:** `string` — the full path to the generated (not yet created) file.

---

### `File\GetLocalFile`

**Class:** `Spipu\ProcessBundle\Step\File\GetLocalFile`

Finds a single file in a folder whose name matches a regular expression pattern. Throws if zero or more than one match is found.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `folder` | string | yes | Directory to search in |
| `file_pattern` | string | yes | Regular expression (without delimiters) matched against the filename |

**Returns:** `string` — the full path to the found file.

---

### `File\ExtractZipFile`

**Class:** `Spipu\ProcessBundle\Step\File\ExtractZipFile`

Extracts a ZIP archive to a directory. Creates the destination directory if it does not exist.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `file` | string | yes | Path to the ZIP file |
| `destination` | string | yes | Extraction directory |

**Returns:** `string` — the destination directory path.

---

### `File\DispatchFiles`

**Class:** `Spipu\ProcessBundle\Step\File\DispatchFiles`

Moves files from a source folder to destination folders according to a mapping of patterns. Each mapping entry must match exactly one file; an exception is thrown if a pattern has no match.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `folder` | string | yes | Source directory containing the files |
| `mapping` | array | yes | List of `{file_pattern: "...", destination: "..."}` entries |
| `keep_only_one_file` | bool | yes | If `true`, deletes pre-existing files matching the pattern in the destination |

**Returns:** `int` — the number of files dispatched.

---

### `File\RemoveFile`

**Class:** `Spipu\ProcessBundle\Step\File\RemoveFile`

Deletes a file. Throws if the path does not point to a file.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `file` | string | yes | File path to delete |

**Returns:** `true`

---

### `File\RemoveFolder`

**Class:** `Spipu\ProcessBundle\Step\File\RemoveFolder`

Removes a directory and all its contents recursively. Throws if the path is not a directory.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `folder` | string | yes | Directory path to remove |

**Returns:** `true`

---

### `File\ArchiveLocalFile`

**Class:** `Spipu\ProcessBundle\Step\File\ArchiveLocalFile`

Moves a file into an archive folder, appending a timestamp to its name. Optionally deletes the oldest archived files to keep only a fixed number.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `filename` | string | yes | Path to the source file to archive |
| `folder` | string | yes | Archive destination directory |
| `keep_number` | int | no | If set (>0), keep at most this many archived files; oldest are deleted |
| `keep_pattern` | string | no | Restrict cleanup to archived files whose name matches this pattern |

**Returns:** `string` — the full path of the archived file.

---

### `File\CleanFiles`

**Class:** `Spipu\ProcessBundle\Step\File\CleanFiles`

Removes the oldest files in a folder, keeping only the N most recent (sorted by filename alphabetically).

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `folder` | string | yes | Directory to clean |
| `keep_number` | int | yes | Number of most-recent files to keep (minimum 1) |

**Returns:** `true`

---

### `File\DisplayFileInfo`

**Class:** `Spipu\ProcessBundle\Step\File\DisplayFileInfo`

Logs metadata about a file (existence, size in KB, modification date). Does not throw if the file does not exist.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `filename` | string | yes | File path |

**Returns:** `true`

---

### `File\ImportFileToTable`

**Class:** `Spipu\ProcessBundle\Step\File\ImportFileToTable`

Imports a file (CSV or fixed-width) into a database table using a configurable `RowReader`. Rows are bulk-inserted in batches of 1000.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `filename` | string | yes | Path to the input file |
| `tablename` | string | yes | Target database table name |
| `row_reader` | array | yes | Row reader definition (see below) |

The `row_reader` array structure:

```yaml
row_reader:
    class: Spipu\ProcessBundle\Step\File\RowReader\Csv   # or RowReader\FixedWidth
    parameters:
        # reader-specific options (e.g. delimiter, encoding)
    fields:
        # field definitions for this reader
    global_actions:
        # optional: list of actions applied to all fields
```

Available row readers: `Spipu\ProcessBundle\Step\File\RowReader\Csv` and `Spipu\ProcessBundle\Step\File\RowReader\FixedWidth`.

**Returns:** `array` with keys `read` (total lines read) and `imported` (lines imported).

---

## Export File Steps

These three steps work together to manage export files. `PrepareExportFile` initializes the file, your own steps write to it, `FinalizeExportFile` closes/moves it, and `CleanExportFiles` removes old copies.

### `ExportFile\PrepareExportFile`

**Class:** `Spipu\ProcessBundle\Step\ExportFile\PrepareExportFile`

Initializes an export file and returns a `FileExportManager` object that subsequent steps use.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `folder_code` | string | yes | Logical folder code registered in `FileManagerInterface` |
| `file_code` | string | yes | Logical file code (used in the filename) |
| `file_extension` | string | yes | File extension without dot (e.g. `"csv"`) |

**Returns:** `FileExportManager` instance.

---

### `ExportFile\FinalizeExportFile`

**Class:** `Spipu\ProcessBundle\Step\ExportFile\FinalizeExportFile`

Finalizes the export file (moves the temp file to its final location).

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `file_export` | FileExportManager | yes | The result of the `PrepareExportFile` step |

**Returns:** `true`

---

### `ExportFile\CleanExportFiles`

**Class:** `Spipu\ProcessBundle\Step\ExportFile\CleanExportFiles`

Removes old export files, keeping only the N most recent.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `file_export` | FileExportManager | yes | The result of the `PrepareExportFile` step |
| `keep_number` | int | yes | Number of most-recent export files to keep (minimum 1) |

**Returns:** `true`

---

## String Steps

### `String\MergeStrings`

**Class:** `Spipu\ProcessBundle\Step\String\MergeStrings`

Joins an array of strings with a separator.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `strings` | array | yes | Array of strings to join |
| `glue` | string | yes | Separator string (can be `""` for no separator) |

**Returns:** `string`

---

### `String\ReplaceStrings`

**Class:** `Spipu\ProcessBundle\Step\String\ReplaceStrings`

Performs one or more search-and-replace operations on a string.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `subject` | string | yes | The string to operate on |
| `replace` | array | yes | Map of `search => replacement` pairs |

**Returns:** `string`

---

### `String\ToUpperString`

**Class:** `Spipu\ProcessBundle\Step\String\ToUpperString`

Converts a string to uppercase using multibyte-aware conversion.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `value` | string | yes | String to convert |

**Returns:** `string`

---

## Database Steps

All database steps use the Doctrine DBAL `Connection` injected via the constructor.

### `Database\CreateTemporaryTable`

**Class:** `Spipu\ProcessBundle\Step\Database\CreateTemporaryTable`

Creates a table (dropping it first if it already exists). The table always gets an auto-increment `id` primary key and a unique-indexed `row_id` column in addition to the declared fields.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `tablename` | string | yes | Table name to create |
| `fields` | array | yes | Map of `field_name => {type: "...", options: {...}}` |
| `charset` | string | no | MySQL charset (default: `utf8mb4`) |
| `collation` | string | no | MySQL collation (default: `utf8mb4_unicode_ci`) |

**Returns:** `string` — the table name.

---

### `Database\RemoveTemporaryTable`

**Class:** `Spipu\ProcessBundle\Step\Database\RemoveTemporaryTable`

Drops a table.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `tablename` | string | yes | Table name to drop |
| `if_exists` | bool | no | If `true`, suppress error when table does not exist (default: `false`) |

**Returns:** `string` — the table name.

---

### `Database\AddIndexToTable`

**Class:** `Spipu\ProcessBundle\Step\Database\AddIndexToTable`

Creates an index on one or more columns of a table. The index name is derived from an MD5 hash of the table and column names; if the index already exists, a warning is logged and no error is thrown.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `tablename` | string | yes | Table to add the index to |
| `fields` | array | yes | List of column names to index |

**Returns:** `bool` — `true` if created, `false` if it already existed.

---

### `Database\CleanDuplicatesData`

**Class:** `Spipu\ProcessBundle\Step\Database\CleanDuplicatesData`

Removes duplicate rows from a table: rows are considered duplicates when they share identical values on all specified fields. The row with the lower `id` is kept.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `tablename` | string | yes | Table to clean |
| `fields` | array | yes | List of column names that define uniqueness |

**Returns:** `array` with keys `duplicated` (count of duplicate groups found) and `purged` (rows deleted).

---

## LoopStep

**Class:** `Spipu\ProcessBundle\Step\LoopStep`

Iterates over a collection, executing a sequence of sub-steps for each element. Progress is reported as the iteration advances.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `iterable` | array (countable) | yes | The collection to iterate over |
| `steps` | array | yes | Sub-step definitions (same structure as a process `steps` block) |

Inside sub-steps, the following additional variables are available:

| Variable | Value |
|----------|-------|
| `{{ loop.key }}` | Current iteration key (always cast to string) |
| `{{ loop.value }}` | Current iteration value |
| `{{ loop.result.<step_code> }}` | Return value of a previous sub-step in this iteration |
| `{{ loop.result }}` | Return value of the last sub-step of the previous iteration |

Example:

```yaml
steps:
    my_loop:
        class: Spipu\ProcessBundle\Step\LoopStep
        parameters:
            iterable: "{{ result.some_step }}"
            steps:
                sub_step_one:
                    class: App\Step\ProcessItem
                    parameters:
                        item: "{{ loop.value }}"
                sub_step_two:
                    class: App\Step\StoreResult
                    parameters:
                        input:  "{{ loop.value }}"
                        result: "{{ loop.result.sub_step_one }}"
```

**Returns:** The return value of the last sub-step of the last iteration (or `null` if the collection was empty).

---

## Test Steps

These steps are for testing and development only. They are included in the bundle but should not be used in production.

| Step class | Description |
|-----------|-------------|
| `Test\HelloWorld` | Logs "Hello World" — minimal smoke test |
| `Test\GenerateError` | Throws an exception — tests failure handling |
| `Test\PrepareQuery` | Prepares a test JSON query string from `agency` and `product_ids` parameters |
| `Test\AnalyseResult` | Logs the content of a `result` parameter |

[back](./README.md)
