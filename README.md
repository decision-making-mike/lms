# LMS

The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT", "SHOULD", "SHOULD NOT", "RECOMMENDED",  "MAY", and "OPTIONAL" in this document are to be interpreted as described in RFC 2119.

The name of this project is "LMS". It is an abbreviation of the phrase "life management software".

## Usage

### Requirements

1. A web server is running,
2. a web browser is running,
3. the server can read and write to the file at the path specified in the file `config.php` as the value for the key `task-file-path`,
4. the server can read the file `index.php`.

### Example usage

If

1. there is installed [the Lynx browser](https://lynx.invisible-island.net/),
2. and the server will interpret the URL `lms` as a request for the file `index.php`,

then LMS can be run as

```
lynx lms
```

## Design

- The user experience in LMS is optimized so LMS be used comfortably in a text-based web browser.
- LMS is to work when JavaScript is disabled, or not supported at all.

## Development

There SHOULD be used the function `htmlspecialchars_with_ent_quotes` instead of the function `htmlspecialchars`. For details, see the description of the `htmlspecialchars_with_ent_quotes` function in the code.

### Code formatting

The length of a line in the `*.php` files SHOULD be at most 50 characters.

There MUST NOT be more than one PHP statement per line.

### Testing

In the course of developing LMS, the developer might think that it would be beneficial to ask a chatbot about the code. If they think the resulting conversation is significant enough, they MAY save it. They MUST save the response (which is obvious), and they SHOULD save the prompt. The response MUST be saved in the file `testing/<chatbot name>/<date>/<conversation running number>/response.txt`, and the prompt, if present, MUST be saved in the file `testing/<chatbot name>/<date>/<conversation running number>/prompt.txt`.
