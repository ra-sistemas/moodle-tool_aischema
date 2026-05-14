# AI Schema Export

A Moodle plugin that exports the complete database schema in formats optimized for AI agents.

## Features

- Export Moodle's complete database schema including all tables, fields, foreign key relationships, and indexes
- Multiple output formats:
  - **Markdown** - Human-readable documentation for AI full-text search and RAG pipelines
  - **JSON** - Structured data for programmatic AI consumption
  - **Mermaid ERD** - Visual entity-relationship diagrams for AI spatial reasoning
  - **Consolidated text** - Flattened format optimized for vector similarity search
- Filter exports by scope: All plugins, Core only, or Plugins only
- Automatic file retention and cleanup via scheduled task

## Installation

Install this plugin like any other Moodle plugin:

1. Copy the `aischema` folder to your Moodle `admin/tool/` directory
2. Visit Site Administration > Notifications to install the plugin
3. The plugin will appear under Site Administration > Development

## Usage

1. Navigate to Site Administration > Development > AI Schema Export
2. Select your desired export scope (All plugins, Core only, or Plugins only)
3. Click "Generate export"
4. Download the generated ZIP file containing all format files
5. Previous exports are listed below with download links

## Settings

- **File retention (days)**: Number of days to keep exported schema files. Set to 0 to keep files indefinitely.

## Requirements

- Moodle 2024100100 (4.3) or higher
- PHP 7.4 or higher

## Privacy

This plugin does not store any personal data. Exported schema files contain only database structure information.

## License

GPL v3 or later - same as Moodle