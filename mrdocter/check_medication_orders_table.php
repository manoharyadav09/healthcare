<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";

// Check if the medication_orders table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'medication_orders'");
if ($result->num_rows > 0) {
    $table_exists = true;
}

// If the table doesn't exist, create it
if (!$table_exists) {
    $create_table_sql = "CREATE TABLE IF NOT EXISTS medication_orders (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        order_date DATE NOT NULL,
        delivery_date DATE NOT NULL,
        status ENUM('pending', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
        total_amount DECIMAL(10,2) NOT NULL,
        items TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($create_table_sql) === TRUE) {
        echo "Table medication_orders created successfully<br>";
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }
} else {
    echo "Table medication_orders already exists<br>";
    
    // Check if the table has the correct structure
    $result = $conn->query("DESCRIBE medication_orders");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = $row;
    }
    
    // Check for missing columns
    $required_columns = [
        'id' => 'INT NOT NULL PRIMARY KEY AUTO_INCREMENT',
        'user_id' => 'INT NOT NULL',
        'order_date' => 'DATE NOT NULL',
        'delivery_date' => 'DATE NOT NULL',
        'status' => "ENUM('pending', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending'",
        'total_amount' => 'DECIMAL(10,2) NOT NULL',
        'items' => 'TEXT NOT NULL',
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
    ];
    
    $missing_columns = [];
    foreach ($required_columns as $column => $definition) {
        if (!isset($columns[$column])) {
            $missing_columns[$column] = $definition;
        }
    }
    
    // Add missing columns
    if (!empty($missing_columns)) {
        echo "Adding missing columns:<br>";
        foreach ($missing_columns as $column => $definition) {
            $alter_sql = "ALTER TABLE medication_orders ADD COLUMN $column $definition";
            if ($conn->query($alter_sql) === TRUE) {
                echo "Column $column added successfully<br>";
            } else {
                echo "Error adding column $column: " . $conn->error . "<br>";
            }
        }
    } else {
        echo "All required columns exist<br>";
    }
    
    // Check for foreign key constraint
    $result = $conn->query("SELECT * FROM information_schema.TABLE_CONSTRAINTS 
                           WHERE CONSTRAINT_TYPE = 'FOREIGN KEY' 
                           AND TABLE_NAME = 'medication_orders'");
    
    if ($result->num_rows == 0) {
        echo "Adding foreign key constraint<br>";
        $alter_sql = "ALTER TABLE medication_orders ADD CONSTRAINT fk_medication_orders_user 
                      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE";
        if ($conn->query($alter_sql) === TRUE) {
            echo "Foreign key constraint added successfully<br>";
        } else {
            echo "Error adding foreign key constraint: " . $conn->error . "<br>";
        }
    } else {
        echo "Foreign key constraint already exists<br>";
    }
}

// Redirect back to health records page
header("location: health_records.php");
exit;
?> 