# LMS

The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT", "SHOULD", "SHOULD NOT", "RECOMMENDED",  "MAY", and "OPTIONAL" in this document are to be interpreted as described in RFC 2119.

The name of this project is "LMS". It is an abbreviation of the phrase "life management software".

## Deployment

### Requirements

1. LMS MUST be deployed on the same machine that it is to be used on. Even though it is designed to use a web server, it is not designed with network security precautions in mind.

## Usage

### Requirements

1. A web server is running.
2. A web browser is running.
3. The server can read and write to the file at the path specified as the value of the server environment variable `LMS_TASK_FILE_PATH`.
4. The server can read the file `index.php`.

### Example usage

If

1. there is installed [the Lynx browser](https://lynx.invisible-island.net/),
2. and the server will interpret the URL `lms` as a request for the file `index.php`,

then LMS can be run as

```
lynx lms
```

## Design

### General design principles

The user experience in LMS is optimized so LMS be used comfortably in a text-based web browser.

LMS is to work when JavaScript is disabled, or not supported at all.

### User API

This is the definition of the user API (it is called "public API" by the [Semantic Versioning](https://semver.org/) specification). This definition is intended to:
- facilitate for the developer to determine whether there should be released a new major, minor, or patch version of LMS given the changes they have made,
- facilitate for the user to determine what changes they should expect when there has been released a major, minor, or patch version of LMS.

- As for the data model:
    - there are tasks. A task is a key-value pair. The key represents the name of the task. The value represents the name of another task, called the parent task of the task.
- As for user operations:
    - there are possible the following user operations:
        - task viewing. It involves specifying the name of the task to view,
        - task addition. It involves specifying the name and parent task of a task, and saving them to the task file,
        - task modification. It involves modifying the name, or (and) the name of the parent task of a task, and saving it (them) to the task file,
        - task removal. It involves specifying the name of the task to remove, and removal confirmation,
    - task `(NA)` can not be modified, or removed.
- As for data presentation:
    - there are the following views:
        - the default view. It shows a task,
        - the all task view,
        - the modification-addition form view,
        - the removal confirmation form view.
- As for files:
    - there are used the following files:
        - the task file. The user can specify its path,
        - the example task file. Its path is `testing/data/tasks.txt`,
        - the configuration file. Its path is `config.php`.
- This API.

## Development

There SHOULD be used the function `htmlspecialchars_with_ent_quotes` instead of the function `htmlspecialchars`. For details, see the description of the `htmlspecialchars_with_ent_quotes` function in the code.

### Code formatting

The length of a line in the `*.php` files SHOULD be at most 50 characters.

There MUST NOT be more than one PHP statement per line.

### Testing

In the course of developing LMS, the developer might think that it would be beneficial to ask a chatbot about the code. If they think the resulting conversation is significant enough, they MAY save it. They MUST save the response (which is obvious), and they SHOULD save the prompt. The response MUST be saved in the file `testing/<chatbot name>/<date>/<conversation running number>/response.txt`, and the prompt, if present, MUST be saved in the file `testing/<chatbot name>/<date>/<conversation running number>/prompt.txt`.
