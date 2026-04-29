# Define app

[Back to README](../README.md)

You will need to define:
- your own application class extending `\Watamelo\AbstractApplication`, with:
  - routing implementation and any initialization in `init()`
  - routing execution in `execute()`
- your single entry point (example: `index.php`) instaciating your app class and calling `run()` on it.
