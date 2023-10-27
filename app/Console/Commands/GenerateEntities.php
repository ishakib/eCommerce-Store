<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateEntities extends Command
{
    protected $signature = 'generate:entities';
    protected $description = 'Generate migrations, models, controllers, services, and requests for an entity';

    public function handle()
    {
        $entityData = $this->getDatabaseStructure();

        foreach ($entityData as $entityName => $columns) {
            $migrationName = 'create_' . Str::snake($entityName) . '_table';
            $baseName = Str::singular(Str::studly($entityName));

            $options = [
                'name' => $baseName,
                '--migration' => true,
            ];

            $this->call('make:migration', [
                'name' => $migrationName,
                '--create' => Str::snake($entityName),
            ]);

            // Create model
            $this->call('make:model', $options);

            // Create controller
            $this->call('make:controller', [
                'name' => $baseName . 'Controller',
                '--model' => $baseName,
            ]);

            // Create request
            $this->call('make:request', [
                'name' => $baseName . 'Request',
            ]);

            // Generate API resource routes for both API and web
            $this->call('make:resource', [
                'name' => $baseName . 'Resource',
            ]);

            // Output success message
            $this->info("Generated migration, model, controller, request, and API resource for entity: $entityName");
        }
    }

    private function addApiResourceRoute($filename, $entityName)
    {
        $pluralEntityName = Str::plural($entityName);
        $controllerName = $entityName . 'Controller';

        // Determine the target file (api.php or web.php)
        $filePath = $filename === 'api.php' ? 'routes/api.php' : 'routes/web.php';

        // Add an API resource route to the specified file
        $this->appendFile(
            base_path($filePath),
            "\nRoute::resource('$pluralEntityName', $controllerName);"
        );
    }

    private function appendFile($path, $content)
    {
        file_put_contents($path, file_get_contents($path) . $content);
    }

    public function getDatabaseStructure(): array
    {
        $entityData = [
            'users' => [
                'id' => 'INT',
                'name' => 'VARCHAR',
                'email' => 'VARCHAR',
                'password' => 'VARCHAR (hashed)',
                'registration_date' => 'DATETIME',
                'last_login' => 'DATETIME',
                'phone' => 'VARCHAR',
                'role' => 'ENUM ("Customer", "Admin")',
            ],
            'addresses' => [
                'id' => 'INT',
                'user_id' => 'INT (Foreign Key)',
                'street_address' => 'VARCHAR',
                'city' => 'VARCHAR',
                'state' => 'VARCHAR',
                'postal_code' => 'VARCHAR',
                'country' => 'VARCHAR',
            ],
            'categories' => [
                'id' => 'INT',
                'name' => 'VARCHAR',
            ],
            'products' => [
                'id' => 'INT',
                'name' => 'VARCHAR',
                'description' => 'TEXT',
                'price' => 'DECIMAL',
                'stock_quantity' => 'INT',
                'manufacturer' => 'VARCHAR',
                'category_id' => 'INT (Foreign Key)',
            ],
            'product_images' => [
                'id' => 'INT',
                'product_id' => 'INT (Foreign Key)',
                'image_url' => 'VARCHAR',
            ],
            'orders' => [
                'id' => 'INT',
                'user_id' => 'INT (Foreign Key)',
                'order_date' => 'DATETIME',
                'status' => 'ENUM ("Cart", "Pending", "Shipped", "Delivered")',
                'total_amount' => 'DECIMAL',
                'shipping_address_id' => 'INT (Foreign Key)',
                'billing_address_id' => 'INT (Foreign Key)',
                'payment_method' => 'VARCHAR',
                'payment_status' => 'ENUM ("Pending", "Completed", "Failed")',
            ],
            'order_items' => [
                'id' => 'INT',
                'order_id' => 'INT (Foreign Key)',
                'product_id' => 'INT (Foreign Key)',
                'quantity' => 'INT',
                'price' => 'DECIMAL',
                'subtotal' => 'DECIMAL',
            ],
            'reviews' => [
                'id' => 'INT',
                'user_id' => 'INT (Foreign Key)',
                'product_id' => 'INT (Foreign Key)',
                'rating' => 'INT',
                'comment' => 'TEXT',
                'created_at' => 'DATETIME',
            ],
            'payments' => [
                'id' => 'INT',
                'order_id' => 'INT (Foreign Key)',
                'amount' => 'DECIMAL',
                'payment_date' => 'DATETIME',
                'payment_method' => 'VARCHAR',
                'payment_status' => 'ENUM ("Pending", "Completed", "Failed")',
            ],
            'admins' => [
                'id' => 'INT',
                'username' => 'VARCHAR',
                'password' => 'VARCHAR (hashed)',
                'email' => 'VARCHAR',
                'profile_picture' => 'VARCHAR',
            ],
            'inventories' => [
                'id' => 'INT',
                'product_id' => 'INT (Foreign Key)',
                'stock_quantity' => 'INT',
                'restock_threshold' => 'INT',
            ],
            'promotions' => [
                'id' => 'INT',
                'code' => 'VARCHAR',
                'description' => 'TEXT',
                'discount_type' => 'ENUM ("Percentage", "Fixed")',
                'discount_amount' => 'DECIMAL',
                'start_date' => 'DATETIME',
                'end_date' => 'DATETIME',
                'min_purchase_amount' => 'DECIMAL',
            ],
            'carts' => [
                'id' => 'INT',
                'user_id' => 'INT (Foreign Key)',
                'product_id' => 'INT (Foreign Key)',
                'quantity' => 'INT',
            ],
            'category_hierarchies' => [
            ],
            'attributes' => [
                'id' => 'INT',
                'name' => 'VARCHAR',
                'description' => 'TEXT',
            ],
            'variant_attributes' => [
                'id' => 'INT',
                'attribute_id' => 'INT (Foreign Key)',
                'name' => 'VARCHAR',
                'description' => 'TEXT',
            ],
            'brands' => [
                'id' => 'INT',
                'name' => 'VARCHAR',
                'description' => 'TEXT',
            ],
            'units' => [
                'id' => 'INT',
                'name' => 'VARCHAR',
                'abbreviation' => 'VARCHAR',
                'description' => 'TEXT',
            ],
        ];

        return $this->convertLaravelMigration($entityData);
    }

    public function convertLaravelMigration($entityData): array
    {
        $laravelEntityData = [];

        foreach ($entityData as $entityName => $columns) {
            $laravelColumns = [];

            foreach ($columns as $columnName => $columnType) {
                // Convert column name to snake_case and replace spaces with underscores
                $laravelColumnName = Str::snake($columnName);

                // Convert column type to Laravel data type
                $laravelColumnType = match (true) {
                    str_contains($columnType, 'INT') => 'integer',
                    str_contains($columnType, 'VARCHAR') => 'string',
                    str_contains($columnType, 'TEXT') => 'text',
                    str_contains($columnType, 'DATETIME') => 'dateTime',
                    default => 'string',
                };

                // Check for 'Foreign Key' and set 'foreign' attribute if found
                $foreign = str_contains($columnType, 'Foreign Key');

                // Add the column to the Laravel columns array
                $laravelColumns[$laravelColumnName] = [
                    'type' => $laravelColumnType,
                    'foreign' => $foreign,
                ];
            }

            $laravelEntityData[$entityName] = $laravelColumns;
        }

        return $laravelEntityData;
    }
}
