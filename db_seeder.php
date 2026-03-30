<?php

require_once __DIR__ . '/includes/db.php';

echo "<pre>";
echo "Starting Database Seeding...\n";

try {
    // Get IDs we can use for foreign key relationships
    $agents = $pdo->query("SELECT id FROM agents")->fetchAll(PDO::FETCH_COLUMN);
    $customers = $pdo->query("SELECT id FROM customers")->fetchAll(PDO::FETCH_COLUMN);
    $cities = $pdo->query("SELECT id FROM cities")->fetchAll(PDO::FETCH_COLUMN);

    if (empty($agents) || empty($customers) || empty($cities)) {
        die("Error: Agents, Customers or Cities table is empty. Please insert base data first.\n");
    }

    // Available shipment statuses

    // Last 6 months
    $months = [
        date('Y-m', strtotime('-5 months')),
        date('Y-m', strtotime('-4 months')),
        date('Y-m', strtotime('-3 months')),
        date('Y-m', strtotime('-2 months')),
        date('Y-m', strtotime('-1 months')),
        date('Y-m')
    ];

    $pdo->beginTransaction();

    foreach ($months as $month_prefix) {

        $shipments_this_month = rand(10, 20);

        echo "Seeding $month_prefix → Generating $shipments_this_month shipments...\n";

        for ($i = 0; $i < $shipments_this_month; $i++) {

            $day = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
            $hour = str_pad(rand(8, 20), 2, '0', STR_PAD_LEFT);
            $minute = str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT);
            $second = str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT);

            $created_at = "$month_prefix-$day $hour:$minute:$second";

            $tracking_number = "CX-" . rand(100000, 999999);

            $agent_id = $agents[array_rand($agents)];
            $customer_id = $customers[array_rand($customers)];

            $origin_city_id = $cities[array_rand($cities)];
            $destination_city_id = $cities[array_rand($cities)];

            // Ensure origin and destination are not same
            while ($origin_city_id == $destination_city_id) {
                $destination_city_id = $cities[array_rand($cities)];
            }

            $status = $statuses[array_rand($statuses)];

            $weight = rand(1, 50) / 2; // 0.5kg - 25kg
            $price = rand(800, 6000);

            // Insert Shipment
            $stmt = $pdo->prepare("
                INSERT INTO shipments 
                (tracking_number, agent_id, customer_id, origin_city_id, destination_city_id, status, weight, price, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $tracking_number,
                $agent_id,
                $customer_id,
                $origin_city_id,
                $destination_city_id,
                $status,
                $weight,
                $price,
                $created_at
            ]);

            $shipment_id = $pdo->lastInsertId();

            // Insert Revenue
            $stmt = $pdo->prepare("
                INSERT INTO revenue 
                (shipment_id, amount, agent_id, transaction_date)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $shipment_id,
                $price,
                $agent_id,
                $created_at
            ]);
        }
    }

    $pdo->commit();

    echo "\nDatabase seeding completed successfully!\n";
    echo "Shipments and revenue data inserted for the last 6 months.\n";

}
catch (PDOException $e) {

    $pdo->rollBack();

    echo "Seeding failed:\n";
    echo $e->getMessage();
}

echo "</pre>";
?>