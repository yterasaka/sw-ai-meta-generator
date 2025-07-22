# AI Meta Generator for Shopware 6

![Image](https://github.com/user-attachments/assets/3ae59fda-1132-4153-81ec-3a89624270aa)

An AI-powered metadata auto-generation plugin for Shopware 6. Automatically generates SEO-optimized meta titles, meta descriptions, and keywords using OpenAI API based on product names and descriptions.

## Features

- **AI-Driven Metadata Generation**: One-click automatic generation of SEO-optimized metadata using OpenAI API
- **Multi-language Support**: Generates metadata in the appropriate language based on product language settings
- **Admin Panel Integration**: Adds "AI Metadata Generation" button to the SEO form in product edit screens
- **Error Handling**: Proper error display and logging when API calls fail

## Requirements

- Shopware 6.6.0 or higher
- PHP 8.1 or higher
- OpenAI API key

## Installation

### 1. Download Plugin

```bash
# Git clone
git clone https://github.com/yterasaka/sw-ai-meta-generator

# Or download and extract ZIP file
```

### 2. Activate Plugin

```bash
# Command line
bin/console plugin:refresh
bin/console plugin:install --activate AiMetaGenerator
bin/console cache:clear
```

Or via admin panel:

1. Extensions > My extensions > Installed
2. Activate "AI Meta Generator"

## Configuration

1. Shopware Admin > Settings > System > Plugins > AI Meta Generator
2. Enter OpenAI API key
3. Save configuration

## Usage

1. Open product edit screen
2. Enter product name and description
3. Click "AI Metadaten generieren" button in SEO tab
4. Auto-generated metadata will be populated in respective fields

## Generated Metadata

- **Meta Title**: 50-60 characters, includes primary keywords
- **Meta Description**: 150-160 characters, engaging and descriptive content
- **Keywords**: 3-5 related SEO keywords (comma-separated)

## License

MIT License

## Contributing

Pull requests are welcome!
If you have any issues or questions, please let us know on the GitHub Issues page.

## Changelog

### v1.0.0 (2025-07-22)

- Initial release
- Basic AI metadata generation functionality
- Multi-language support
- Admin panel integration
