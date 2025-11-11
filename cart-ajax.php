<?php
// cart-ajax.php - FIXED VERSION
session_start();

// FIXED: Correct path for includes
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

header('Content-Type: application/json');

// Enable CORS for local development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Simple error handler for JSON responses
function sendError($message) {
    echo json_encode(['error' => $message, 'status' => 'error']);
    exit();
}

if (isset($_GET['action'])) {
    try {
        switch ($_GET['action']) {
            case 'get_count':
                $count = 0;
                if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                    $count = array_sum($_SESSION['cart']);
                }
                echo json_encode(['count' => $count, 'status' => 'success']);
                break;
                
            case 'get_preview':
                $items = [];
                $total = 0;
                $item_count = 0;
                
                if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                    $conn = getDatabaseConnection();
                    if ($conn) {
                        $product_ids = array_keys($_SESSION['cart']);
                        if (!empty($product_ids)) {
                            $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
                            
                            $stmt = $conn->prepare("SELECT product_id, name, price, image_path FROM products WHERE product_id IN ($placeholders) AND is_active = 1");
                            if ($stmt) {
                                $types = str_repeat('i', count($product_ids));
                                $stmt->bind_param($types, ...$product_ids);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                
                                while ($product = $result->fetch_assoc()) {
                                    $quantity = $_SESSION['cart'][$product['product_id']];
                                    $subtotal = $product['price'] * $quantity;
                                    $total += $subtotal;
                                    $item_count += $quantity;
                                    
                                    $items[] = [
                                        'id' => $product['product_id'],
                                        'name' => $product['name'],
                                        'price' => floatval($product['price']),
                                        'quantity' => $quantity,
                                        'subtotal' => $subtotal,
                                        'image' => !empty($product['image_path']) ? $product['image_path'] : 'includes/Screenshot 2025-10-30 145731.png'
                                    ];
                                }
                                $stmt->close();
                            }
                        }
                        $conn->close();
                    }
                }
                
                echo json_encode([
                    'items' => $items, 
                    'total' => $total,
                    'item_count' => $item_count,
                    'status' => 'success'
                ]);
                break;
                
            default:
                sendError('Invalid action');
                break;
        }
    } catch (Exception $e) {
        sendError('Server error: ' . $e->getMessage());
    }
} else {
    sendError('No action specified');
}
?>