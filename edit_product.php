<?php
session_start();
require_once 'assets/config.php';

// Redirect to login page if not logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// Fetch user information
$user_email = $_SESSION['user'];
$db = getDB();
$stmt = $db->prepare("SELECT id, name, role FROM users WHERE email = ?");
$stmt->bind_param('s', $user_email);
$stmt->execute();
$stmt->bind_result($user_id, $user_name, $user_role);
$stmt->fetch();
$stmt->close();

// Check if the user is an admin or manager
$is_admin = ($user_role === 'Admin');
$is_manager = ($user_role === 'Manager');

if (!$is_admin && !$is_manager) {
    header('Location: products.php');
    exit();
}

// Fetch product information
$product_id = $_GET['id'];
$stmt = $db->prepare("SELECT product_name FROM products WHERE id = ?");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$stmt->bind_result($product_name);
$stmt->fetch();
$stmt->close();

// Fetch sub-products information
$sub_products = $db->query("SELECT * FROM sub_products WHERE product_id = $product_id")->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = $_POST['product_name'];
    $sub_products = $_POST['sub_products'];

    $stmt = $db->prepare("UPDATE products SET product_name = ? WHERE id = ?");
    $stmt->bind_param('si', $product_name, $product_id);
    $stmt->execute();
    $stmt->close();

    $db->query("DELETE FROM sub_products WHERE product_id = $product_id");

    $stmt = $db->prepare("INSERT INTO sub_products (product_id, sub_product_name) VALUES (?, ?)");
    foreach ($sub_products as $sub_product_name) {
        $stmt->bind_param('is', $product_id, $sub_product_name);
        $stmt->execute();
    }
    $stmt->close();

    header('Location: products.php');
    exit();
}

$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #1C1C1C, #2C2C2C);
            color: #E0E0E0;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
        }

        .edit-container {
            background: rgba(68, 68, 68, 0.9);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 500px;
            text-align: left;
            animation: slideIn 1s ease-in-out;
            position: relative;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .edit-container h1 {
            font-size: 30px;
            margin-bottom: 20px;
            color: #00BFA5;
            animation: fadeIn 2s ease-in-out;
            text-align: center;
        }

        .edit-container form {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            position: relative;
        }

        .edit-container label {
            font-size: 16px;
            margin-bottom: 5px;
            color: #E0E0E0;
        }

        .edit-container input[type="text"], .edit-container input[type="submit"], .edit-container .cancel-button {
            padding: 10px;
            margin-bottom: 20px;
            width: 100%;
            border: none;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.1);
            color: #E0E0E0;
            border: 1px solid #555;
            transition: background 0.3s, transform 0.3s;
        }

        .edit-container input[type="text"]:focus, .edit-container .cancel-button:focus {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }

        .edit-container input[type="submit"]:hover, .edit-container .cancel-button:hover {
            background: #009688;
            transform: scale(1.05);
        }

        .edit-container .sub-product-fields {
            margin-bottom: 20px;
            width: 100%;
        }

        .edit-container .sub-product {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .edit-container .sub-product label {
            flex: 1;
            margin-right: 10px;
        }

        .edit-container .sub-product input {
            flex: 4;
            margin-right: 10px;
        }

        .edit-container .sub-product .remove-sub-product {
            flex: 0;
            background: none;
            border: none;
            color: #FF073A;
            cursor: pointer;
            font-size: 18px;
            margin-left: 10px;
            transition: color 0.3s;
        }

        .edit-container .sub-product .remove-sub-product:hover {
            color: #CC0625;
        }

        .edit-container .add-sub-product {
            background: #00BFA5;
            border: none;
            padding: 10px;
            color: #E0E0E0;
            cursor: pointer;
            transition: background 0.3s, transform 0.3s;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .edit-container .add-sub-product:hover {
            background: #009688;
            transform: scale(1.05);
        }

        .edit-container .submit-button {
            width: 100%;
            text-align: center;
            margin-bottom: 10px;
        }

        .edit-container .submit-button input {
            width: 100%;
            max-width: 200px;
            background: #00BFA5;
            color: #E0E0E0;
            cursor: pointer;
        }

        .edit-container .submit-button input:hover {
            background: #009688;
        }

        .edit-container .cancel-button {
            padding: 10px;
            border: none;
            border-radius: 5px;
            background: #FF073A;
            color: #E0E0E0;
            cursor: pointer;
            transition: background 0.3s, transform 0.3s;
            width: 100%;
            max-width: 200px;
            text-align: center;
            margin-top: 0;
            align-self: center; /* Centering the cancel button */
        }

        .edit-container .cancel-button:hover {
            background: #CC0625;
            transform: scale(1.05);
        }

        .edit-container .close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            color: #FF073A;
            font-size: 24px;
            cursor: pointer;
        }

        .edit-container .close-button:hover {
            color: #CC0625;
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <button class="close-button" onclick="window.location.href='products.php'">&times;</button>
        <h1>Edit Product</h1>
        <form method="POST" action="edit_product.php?id=<?php echo $product_id; ?>">
            <label for="product_name">Product Name</label>
            <input type="text" id="product_name" name="product_name" value="<?php echo htmlspecialchars($product_name); ?>" required>
            
            <div class="sub-product-fields">
                <?php
                $index = 1;
                foreach ($sub_products as $sub_product): ?>
                    <div class="sub-product">
                        <label for="sub_product_<?php echo $index; ?>">Sub Product <?php echo $index; ?></label>
                        <input type="text" id="sub_product_<?php echo $index; ?>" name="sub_products[]" value="<?php echo htmlspecialchars($sub_product['sub_product_name']); ?>" required>
                        <button type="button" class="remove-sub-product">&times;</button>
                    </div>
                <?php 
                $index++;
                endforeach; ?>
            </div>

            <button type="button" class="add-sub-product">+</button>
            
            <div class="submit-button">
                <input type="submit" value="Create Product">
                <button type="button" class="cancel-button" onclick="window.location.href='products.php'">Cancel</button>
            </div>
        </form>
        <!-- <button class="cancel-button" onclick="window.location.href='products.php'">Cancel</button> -->
    </div>
    <script>
        let subProductCount = <?php echo count($sub_products); ?>;

        document.querySelector('.add-sub-product').addEventListener('click', function() {
            subProductCount++;
            const subProductFields = document.querySelector('.sub-product-fields');
            const newField = document.createElement('div');
            newField.classList.add('sub-product');
            newField.innerHTML = `
                <label for="sub_product_${subProductCount}">Sub Product ${subProductCount}</label>
                <input type="text" id="sub_product_${subProductCount}" name="sub_products[]" required>
                <button type="button" class="remove-sub-product">&times;</button>
            `;
            subProductFields.appendChild(newField);
        });

        document.querySelector('.sub-product-fields').addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-sub-product')) {
                e.target.parentElement.remove();
                updateLabels();
            }
        });

        function updateLabels() {
            const subProducts = document.querySelectorAll('.sub-product');
            subProducts.forEach((subProduct, index) => {
                const label = subProduct.querySelector('label');
                label.setAttribute('for', `sub_product_${index + 1}`);
                label.textContent = `Sub Product ${index + 1}`;
                const input = subProduct.querySelector('input');
                input.setAttribute('id', `sub_product_${index + 1}`);
            });
            subProductCount = subProducts.length;
        }
    </script>
</body>
</html>
