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
            $baseName = Str::singular(Str::studly($entityName));
            $migrationName = 'create_' . $entityName . '_table';
            $this->call('make:migration', [
                'name' => $migrationName,
                '--create' => $entityName,
            ]);
            $this->addColumnsToMigration($migrationName, $columns);

            // Create model, controller, request, and resource
            $modelName = Str::singular(Str::studly($entityName));
            $this->call('make:model', [
                'name' => $modelName,
                '--migration' => true,
            ]);

            // Add fillable property to the generated model
            $this->addFillablePropertyToModel($baseName, array_keys($columns));

            $controllerName = $modelName . 'Controller';
            $this->call('make:controller', [
                'name' => $controllerName,
                '--model' => $modelName,
            ]);

            $requestName = $modelName . 'Request';
            $this->call('make:request', [
                'name' => $requestName,
            ]);

            $resourceName = $modelName . 'Resource';
            $this->call('make:resource', [
                'name' => $resourceName,
            ]);

            // Output success message
            $this->info("Generated migration, model, controller, request, and API resource for entity: $entityName");
        }
    }

    private function addColumnsToMigration($migrationName, $columns)
    {
        $migrationFileName = $this->getMigrationFileName($migrationName);
        $migrationPath = database_path('migrations') . '/' . $migrationFileName;

        if (!file_exists($migrationPath)) {
            $this->error("Migration file not found: $migrationPath");
            return;
        }

        // Read the migration file contents
        $contents = file_get_contents($migrationPath);

        // Find the position to insert column definitions
        $pos = strpos($contents, '$table->id();');
        if ($pos === false) {
            return; // Handle the case where '$table->id();' is not found
        }

        // Initialize the column definitions
        $columnDefinitions = '';
        foreach ($columns as $columnName => $columnType) {
            $columnDefinitions .= "\n\t\t\t\$table->$columnType('$columnName');";
        }

        // Insert the generated column definitions integero the migration file
        $contents = substr_replace($contents, $columnDefinitions, $pos + 13, 0);

        // Write the updated contents back to the migration file
        file_put_contents($migrationPath, $contents);
    }

    private function getMigrationFileName($migrationName): string
    {
        $timestamp = now()->format('Y_m_d_His');
        return $timestamp . '_' . $migrationName . '.php';
    }

    private function addFillablePropertyToModel($modelName, $columns)
    {
        $fillableProperty = "protected \$fillable = [\n    '" . implode("',\n    '", $columns) . "'\n];";

        $modelPath = app_path("Models/{$modelName}.php");
        $modelContent = file_get_contents($modelPath);

        // Replace or add the fillable property
        $modelContent = preg_replace('/protected \$fillable = \[.*\];/s', $fillableProperty, $modelContent, 1);

        file_put_contents($modelPath, $modelContent);
    }

    private function getDatabaseStructure(): array
    {
        return [
            'users' => [
                'name' => 'string',
                'email' => 'email',
                'password' => 'string',
                'registration_date' => 'date',
                'last_login' => 'dateTime',
                'phone' => 'string',
            ],
            'addresses' => [
                'user_id' => 'foreignId',
                'street_address' => 'string',
                'city' => 'string',
                'state' => 'string',
                'postal_code' => 'string',
                'country' => 'string',
            ],
            'categories' => [
                'name' => 'string',
            ],
            'products' => [
                'name' => 'string',
                'description' => 'text',
                'price' => 'decimal',
                'stock_quantity' => 'integer',
                'manufacturer' => 'string',
                'category_id' => 'foreignId',
            ],
            'product_images' => [
                'product_id' => 'foreignId',
                'image_url' => 'string',
            ],
            'orders' => [
                'user_id' => 'foreignId',
                'order_date' => 'dateTime',
                'total_amount' => 'decimal',
                'shipping_address_id' => 'foreignId',
                'billing_address_id' => 'foreignId',
                'payment_method' => 'string',
            ],
            'order_items' => [
                'order_id' => 'foreignId',
                'product_id' => 'foreignId',
                'quantity' => 'integer',
                'price' => 'decimal',
                'subtotal' => 'decimal',
            ],
            'reviews' => [
                'user_id' => 'foreignId',
                'product_id' => 'foreignId',
                'rating' => 'integer',
                'comment' => 'text',
                'created_at' => 'dateTime',
            ],
            'payments' => [
                'order_id' => 'foreignId',
                'amount' => 'decimal',
                'payment_date' => 'dateTime',
                'payment_method' => 'string',
            ],
            'admins' => [
                'username' => 'string',
                'password' => 'string',
                'email' => 'string',
                'profile_picture' => 'string',
            ],
            'inventories' => [
                'product_id' => 'foreignId',
                'stock_quantity' => 'integer',
                'restock_threshold' => 'integer',
            ],
            'promotions' => [
                'code' => 'string',
                'description' => 'text',
                'discount_amount' => 'decimal',
                'start_date' => 'dateTime',
                'end_date' => 'dateTime',
                'min_purchase_amount' => 'decimal',
            ],
            'carts' => [
                'user_id' => 'foreignId',
                'product_id' => 'foreignId',
                'quantity' => 'integer',
            ],
            'category_hierarchies' => [
            ],
            'attributes' => [
                'name' => 'string',
                'description' => 'text',
            ],
            'variant_attributes' => [
                'attribute_id' => 'foreignId',
                'name' => 'string',
                'description' => 'text',
            ],
            'brands' => [
                'name' => 'string',
                'description' => 'text',
            ],
            'units' => [
                'name' => 'string',
                'abbreviation' => 'string',
                'description' => 'text',
            ],
        ];
    }
}
