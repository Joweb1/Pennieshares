<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo->exec("UPDATE company_funds SET total_reservation_fund = 0, total_company_profit = 0, total_generational_pot = 0, total_shared_pot = 0;");
    echo "Reservation funds and company profit cleared successfully.";
} catch (PDOException $e) {
    echo "Error clearing company funds: " . $e->getMessage();
}
?>