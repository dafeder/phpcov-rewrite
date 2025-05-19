# PHPUnit Coverage File Renamer

Script to change file paths in a PHPUnit .cov file when they do not reflect the
current environment. Useful for if you run tests in one CI job and need to
generate an HTML coverage report in another, where the paths have changed.
