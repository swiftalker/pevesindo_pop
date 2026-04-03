---
name: filament-development
description: "This guide covers best practices, rules, and quick references for building admin panels with FilamentPHP v5.x. Apply this skill whenever writing, reviewing, or refactoring FilamentPHP code. This include about resources, tables, schemas, forms, infolists, actions, notifications, widgets, configuration, navigation, users, styling, advanced things, testing, information about components. Always refer to the official documentation: https://filamentphp.com/docs/5.x"
license: MIT
metadata:
  author: laravel
---

# Important things
- For specific use of FilamentPHP for Pop-App applications, you can see the `reference` in the `.agents/skills/filament-development/reference` folder.
- We using FilamentPHP 5.x version. 
- 4.x version maybe *still support* but 5.x is prioritized.
- Cause you doesnt have information about Filament 5.x or 4.x, you can read directory from `.agents/skills/filament-development/references/5.x` and   `.agents/skills/filament-development/references/4.x` to find information about Filament 5.x and 4.x.
- Firstly, you need to READ upgrade guide from `.agents/skills/filament-development/references/4.x/14-upgrade-guide.md` to find information about Filament 4.x.
- Secondly, you need to READ upgrade guide from `.agents/skills/filament-development/references/5.x/15-upgrade-guide.md` to find information about Filament 5.x.
- After you understand about Filament 4.x and 5.x, you can start to build FilamentPHP admin panel.
- Make sure you dont waste time to read everytime, if you was understand then you just specifically refer to the file documentation. REMEMBER: Dont waste time to read again and again.

# Basic Concepts of Resources
Resources are static classes that build CRUD interfaces for Eloquent models.

Resource = Model + Form + Table + Pages (List, Create, Edit, View)

When to use a Resource?
* Every Eloquent model that needs to be managed by an admin
* Standard or highly customized CRUD
* Managing relationships (HasMany, BelongsToMany, etc.)

When to use a Simple Resource?
* Simple models that only need one page
* Prefer modals for create/edit/delete (no dedicated pages)

# Creating a Resource
Basic Commands
```bash
# Standard resource
php artisan make:filament-resource Customer

# Simple resource (modal-based, single page)
php artisan make:filament-resource Customer --simple

# Auto-generate form & table from database columns
php artisan make:filament-resource Customer --generate

# With soft delete support
php artisan make:filament-resource Customer --soft-deletes

# With View page
php artisan make:filament-resource Customer --view

# Custom model namespace
php artisan make:filament-resource Customer --model-namespace=Custom\\Path\\Models

# Generate model, migration, factory all at once
php artisan make:filament-resource Customer --model --migration --factory

# Flag combinations
php artisan make:filament-resource Customer --generate --soft-deletes --view

# Command Line for Resource
```php
❯ art make:filament-resource -h
Description:
  Create a new Filament resource class and default page classes

Usage:
  make:filament-resource [options] [--] [<model>]
  filament:make-resource
  filament:resource

Arguments:
  model                                                The name of the model to generate the resource for, optionally prefixed with directories

Options:
  -C, --cluster[=CLUSTER]                              The cluster to create the resource in
      --embed-schemas                                  Embed the form and infolist schemas in the resource class instead of creating separate files
      --embed-table                                    Embed the table in the resource class instead of creating a separate file
      --factory                                        Create a factory for the model
  -G, --generate                                       Generate the form schema and table columns from the current database columns
      --migration                                      Create a migration for the model
      --model                                          Create the model class if it does not exist
      --model-namespace=MODEL-NAMESPACE                The namespace of the model class, [App\Models] by default
  -N, --nested[=NESTED]                                Nest the resource inside another through a relationship [default: false]
      --not-embedded                                   Even if the resource is simple, create separate files for the form and infolist schemas and table
      --panel=PANEL                                    The panel to create the resource in
      --record-title-attribute=RECORD-TITLE-ATTRIBUTE  The title attribute, used to label each record in the UI
      --resource-namespace[=RESOURCE-NAMESPACE]        The namespace of the resource class, such as [App\Filament\Resources]
  -S, --simple                                         Generate a simple resource class with a single page, modals and embedded schemas and embedded table
      --soft-deletes                                   Indicate if the model uses soft-deletes
      --view                                           Generate a view page / modal for the resource
  -F, --force                                          Overwrite the contents of the files if they already exist
  -h, --help                                           Display help for the given command. When no command is given display help for the list command
      --silent                                         Do not output any message
  -q, --quiet                                          Only errors are displayed. All other output is suppressed
  -V, --version                                        Display this application version
      --ansi|--no-ansi                                 Force (or disable --no-ansi) ANSI output
  -n, --no-interaction                                 Do not ask any interactive question
      --env[=ENV]                                      The environment the command should run under
  -v|vv|vvv, --verbose                                 Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

# Filament Development
Filament is a full-stack framework for Laravel that provides a rich set of tools for building administrative interfaces, forms, tables, and more. This skill covers Filament v5 and v4 development patterns.

## Quick Reference

### 1. Core Concepts

- **Resources**: The primary way to interact with models in Filament. They handle CRUD operations, list views, and detail pages.
##### Detail About Resources
1. Key-Principles: Because we are using Laravel 13, we will use Filament v5. And you need to refer docs first from `https://filamentphp.com/docs/v5` or `.agents/skills/filament-development/referencex/5.x/'filamentphp filament 5.x resources'/*.md`

- **Panels**: Top-level containers for your Filament application. You can have multiple panels (e.g., admin, customer).
##### Detail About Panels
1. Key-Principles: Because we are using Laravel 13, we will use Filament v5. And you need to refer docs first from `https://filamentphp.com/docs/v5` or `.agents/skills/filament-development/referencex/5.x/'filamentphp filament 5.x panels'/*.md`

- **Widgets**: Reusable UI components that can be placed on dashboards or resource pages.
##### Detail About Widgets
1. Key-Principles: Because we are using Laravel 13, we will use Filament v5. And you need to refer docs first from `https://filamentphp.com/docs/v5` or `.agents/skills/filament-development/referencex/5.x/'filamentphp filament 5.x widgets'/*.md`

- **Actions**: Reusable actions that can be attached to resources, tables, or widgets.
##### Detail About Actions
1. Key-Principles: Because we are using Laravel 13, we will use Filament v5. And you need to refer docs first from `https://filamentphp.com/docs/v5` or `.agents/skills/filament-development/referencex/5.x/'filamentphp filament 5.x actions'/*.md`

- **Forms**: A powerful form builder that works seamlessly with Livewire and Eloquent.
##### Detail About Forms
1. Key-Principles: Because we are using Laravel 13, we will use Filament v5. And you need to refer docs first from `https://filamentphp.com/docs/v5` or `.agents/skills/filament-development/referencex/5.x/'filamentphp filament 5.x forms'/*.md`

- **Tables**: A flexible table builder that supports sorting, filtering, searching, and bulk actions.
##### Detail About Tables
1. Key-Principles: Because we are using Laravel 13, we will use Filament v5. And you need to refer docs first from `https://filamentphp.com/docs/v5` or `.agents/skills/filament-development/referencex/5.x/'filamentphp filament 5.x tables'/*.md`

- **Infolists**: A powerful infolist builder that works seamlessly with Livewire and Eloquent.
##### Detail About Infolists
1. Key-Principles: Because we are using Laravel 13, we will use Filament v5. And you need to refer docs first from `https://filamentphp.com/docs/v5` or `.agents/skills/filament-development/referencex/5.x/'filamentphp filament 5.x infolists'/*.md`
- **Notifications**: A powerful notification system that works seamlessly with Livewire and Eloquent.
##### Detail About Notifications
1. Key-Principles: Because we are using Laravel 13, we will use Filament v5. And you need to refer docs first from `https://filamentphp.com/docs/v5` or `.agents/skills/filament-development/referencex/5.x/'filamentphp filament 5.x notifications'/*.md`

- **Users**: By default, all App\Models\Users can access Filament locally. To allow them to access Filament in production, you must take a few extra steps to ensure that only the correct users have access to the app.
##### Detail About Users
1. Key-Principles: Because we are using Laravel 13, we will use Filament v5. And you need to refer docs first from `https://filamentphp.com/docs/v5` or `.agents/skills/filament-development/referencex/5.x/'filamentphp filament 5.x users'/*.md`

- **Styling**:In the configuration, you can easily change the colors that are used. Filament ships with 6 predefined colors that are used everywhere within the framework. They are customizable as follows:
##### Detail About Styling
1. Key-Principles: Because we are using Laravel 13, we will use Filament v5. And you need to refer docs first from `https://filamentphp.com/docs/v5` or `.agents/skills/filament-development/referencex/5.x/'filamentphp filament 5.x styling'/*.md`

- **Schemas**: Schemas are PHP configuration objects that define UI structure declaratively. Instead of writing HTML/JavaScript, you create schema objects that control server-side rendering.
##### Detail About Schemas
1. Key-Principles: Because we are using Laravel 13, we will use Filament v5. And you need to refer docs first from `https://filamentphp.com/docs/v5` or `.agents/skills/filament-development/referencex/5.x/'filamentphp filament 5.x schemas'/*.md`
2. What are schemas?
Schemas are PHP configuration objects that define UI structure declaratively. Instead of writing HTML/JavaScript, you create schema objects that control server-side rendering.
**Key principle:** Schemas are containers that hold components (fields, entries, layouts). Components can nest other schemas, enabling infinite nesting levels.

3. Core Components
| Component Type | Purpose |
|----------------|---------|
| **Form Fields** | Accept user input (text, select, checkbox). Includes built-in validation. |
| **Infolist Entries** | Render read-only key-value pairs (text, icons, images). Data typically comes from Eloquent records. |
| **Layout Components** | Structure components (grid, tabs, multi-step wizards). |
| **Prime Components** | Basic static content (text, images, action buttons). |
- **Components**: Filament packages consume a set of core components that aim to provide a consistent and maintainable foundation for all interfaces. Some of these components are also available for use in your own applications and Filament plugins.

##### Detail About Components
1. Key-Principles: Because we are using Laravel 13, we will use Filament v5. And you need to refer docs first from `https://filamentphp.com/docs/v5` or `.agents/skills/filament-development/referencex/5.x/'filamentphp filament 5.x components'/*.md`



### 2. Common Patterns

#### Creating a Resource

```php
php artisan make:filament-resource Product --generate
```

#### Building a Form

```php
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

TextInput::make('name')
    ->required()
    ->maxLength(255),

Select::make('category_id')
    ->relationship('category', 'name')
    ->required(),
```

#### Building a Table

```php
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

TextColumn::make('name')
    ->searchable()
    ->sortable(),

SelectFilter::make('category')
    ->relationship('category', 'name')
    ->searchable()
    ->preload(),
```

#### Using Widgets

```php
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Products', Product::count()),
            Stat::make('Active Products', Product::where('is_active', true)->count()),
        ];
    }
}
```

#### Actions

```php
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

ActionGroup::make([
    Action::make('publish')
        ->requiresConfirmation()
        ->color('success')
        ->icon('heroicon-o-check-circle')
        ->action(function ($record) {
            $record->update(['is_active' => true]);
        }),
]);
```

### 3. Best Practices

- **Use generated code as a starting point**: Filament generates a lot of code for you. Use it as a base and customize it as needed.
- **Keep resources focused**: Each resource should focus on a single model or a closely related set of models.
- **Use widgets for dashboards**: Don't clutter your resource pages with too many widgets. Create a dedicated dashboard resource for complex analytics.
- **Leverage Filament's form builder**: Filament's form builder is very powerful. Use it to create complex forms with validation and conditional logic.
- **Use Filament's table builder**: Filament's table builder handles sorting, filtering, searching, and pagination for you. Don't reinvent the wheel.
- **Follow Filament's naming conventions**: This makes your code easier to understand and maintain.

### 4. Common Issues

- **N+1 queries**: Ensure you're eager loading relationships in your table queries.
- **Performance**: For large datasets, use pagination and lazy loading.
- **Customization**: When customizing generated code, make backups or use version control to avoid losing your changes.

### 5. Resources

- [Filament Documentation](https://filamentphp.com/docs)
- [Filament v4 Documentation](https://filamentphp.com/docs/v4)
- [Filament v5 Documentation](https://filamentphp.com/docs/v5)
- [Filament GitHub](https://github.com/filamentphp/filament)

### 6. License

This skill is licensed under the MIT License.