<?php
require_once 'Database.php';

try {
    $pdo = Database::getInstance()->getConnection();
    echo "✅ Connection successful!<br>";

    //Create database
    $sql = file_get_contents("database_creation.txt");

    $pdo->exec($sql);
    echo "✅ Database created successfully!<br>";

    //Clear  table
        $sql = "
            TRUNCATE TABLE USER;
            TRUNCATE TABLE CART;
            TRUNCATE TABLE CART_PRODUCTS;
            TRUNCATE TABLE CATEGORY;
            TRUNCATE TABLE COMPANY;
            TRUNCATE TABLE COMPANY_PRODUCTS;
            TRUNCATE TABLE NEWSLETTER;
            TRUNCATE TABLE ORDER_PRODUCTS;
            TRUNCATE TABLE PRODUCT;
            TRUNCATE TABLE REVIEW;
            TRUNCATE TABLE SHOPPING_ORDER;
            ";
    $pdo->exec($sql);

    //Insert sample data
    /*
    $sampleData = [
        [
            'titlu' => 'Breaking News: PHP 8.1 Released',
            'short_description' => 'PHP 8.1 comes with new features and improvements.',
            'description' => 'The PHP development team announces the immediate availability of PHP 8.1. This release includes new features such as enums, readonly properties, and performance improvements.'
        ],
        [
            'titlu' => 'Web Development Trends in 2024',
            'short_description' => 'An overview of the latest trends in web development for 2024.',
            'description' => 'As we move further into 2024, web development continues to evolve with new technologies and frameworks. This article explores the top trends that developers should watch out for this year.'
        ],
        [
            'titlu' => 'How to Secure Your Web Applications',
            'short_description' => 'Best practices for securing web applications against common threats.',
            'description' => 'Security is a critical aspect of web development. This article discusses various strategies and best practices to help developers protect their web applications from vulnerabilities and attacks.'
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO stiri (titlu, short_description, description) VALUES (:titlu, :short_description, :description)");
    foreach ($sampleData as $news) {
        $stmt->execute
        ([
            ':titlu' => $news['titlu'],
            ':short_description' => $news['short_description'],
            ':description' => $news['description']
        ]);
    }
        */

} catch (PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage());
}
