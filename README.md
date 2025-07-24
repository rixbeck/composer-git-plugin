# Composer Git Installer Plugin

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

A Composer plugin that enables `git+<repo>@<branch>` syntax for installing packages directly from Git repositories.

## Features

- Supports `git+https://` and `git+ssh://` URLs
- Automatic package name resolution
- Security validation pipeline
- Error handling with recovery strategies
- Support for subdirectories in repositories
- Extensible architecture for custom parsers and validators

## Installation

### From Packagist

```bash
composer global require neologik/composer-git-installer
```

### From Source (Git)

To install the plugin from source when it's not published on Packagist:

1. **Clone the repository:**
   ```bash
   git clone https://github.com/rixbeck/composer-git-installer.git
   cd composer-git-installer
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Add the local repository to Composer's global configuration:**
   ```bash
   # Add local repository to global config
   composer global config repositories.local-git-installer path `pwd`
   
   # Set minimum-stability to dev for local development
   composer global config minimum-stability dev
   ```

4. **Install the plugin globally from the local source:**
   ```bash
   composer global require neologik/composer-git-installer:dev-main
   ```

> **Note:** Since this is a Composer plugin, it must be installed globally to be available across all projects. The plugin cannot be installed directly from Git using `composer global require` without first setting up a local repository configuration, as shown above.

## Usage

Use the `git+` syntax in your require commands:

```bash
composer require git+https://github.com/owner/repo@branch
```

> **Note:** If you encounter a minimum-stability error (e.g., "Could not find a version of package matching your minimum-stability (stable)"), you may need to adjust your Composer stability settings:
>
> ```bash
> composer config minimum-stability dev
> ```
>
> Or, for a single command:
>
> ```bash
> composer require --prefer-stable git+https://github.com/owner/repo@branch
> ```

This will:
1. Parse the git+ URL
2. Validate the repository and reference
3. Register the repository with Composer
4. Install the package

## Configuration

Add allowed hosts in your composer.json:

```json
{
    "extra": {
        "git-installer-allowed-hosts": [
            "github.com",
            "gitlab.com",
            "your-custom-git-server.com"
        ]
    }
}
```

## Requirements

- PHP 8.1+
- Composer 2.6+

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.