<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Product</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #1C1C1C;
            color: #E0E0E0;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .create-container {
            background: rgba(50, 50, 50, 0.9);
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

        .create-container h1 {
            font-size: 30px;
            margin-bottom: 20px;
            color: #00BFA5;
            animation: fadeIn 2s ease-in-out;
            text-align: center;
        }

        .create-container form {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            position: relative;
        }

        .create-container label {
            font-size: 16px;
            margin-bottom: 5px;
            color: #E0E0E0;
        }

        .create-container input[type="text"],
        .create-container input[type="submit"] {
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

        .create-container input[type="text"]:focus {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }

        .create-container input[type="submit"]:hover {
            background: #009688;
            transform: scale(1.05);
        }

        .create-container .sub-product-fields {
            margin-bottom: 20px;
            width: 100%;
        }

        .create-container .sub-product {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .create-container .sub-product label {
            flex: 1;
            margin-right: 10px;
        }

        .create-container .sub-product input {
            flex: 4;
            margin-right: 10px;
        }

        .create-container .sub-product .remove-sub-product {
            flex: 0;
            background: none;
            border: none;
            color: #FF073A;
            cursor: pointer;
            font-size: 18px;
            margin-left: 10px;
            transition: color 0.3s;
        }

        .create-container .sub-product .remove-sub-product:hover {
            color: #CC0625;
        }

        .create-container .add-sub-product {
            background: #00BFA5;
            border: none;
            padding: 5px;
            color: #E0E0E0;
            cursor: pointer;
            transition: background 0.3s, transform 0.3s;
            font-size: 20px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
            margin-bottom: 20px;
        }

        .create-container .add-sub-product:hover {
            background: #009688;
            transform: scale(1.05);
        }

        .create-container .submit-button {
            width: 100%;
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .create-container .submit-button input,
        .create-container .submit-button .cancel-button {
            width: 100%;
            max-width: 200px;
            align-self: center;
            border: none;
            border-radius: 5px;
            padding: 10px 0;
            color: #E0E0E0;
            cursor: pointer;
            transition: background 0.3s, transform 0.3s;
        }

        .create-container .submit-button .cancel-button {
            background: #FF073A;
        }

        .create-container .submit-button input:hover,
        .create-container .submit-button .cancel-button:hover {
            transform: scale(1.05);
        }

        .create-container .submit-button input {
            background: #00BFA5;
        }

        .create-container .close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            color: #FF073A;
            font-size: 24px;
            cursor: pointer;
        }

        .create-container .close-button:hover {
            color: #CC0625;
        }
    </style>
</head>
<body>
    <div class="create-container">
        <button class="close-button" onclick="window.location.href='products.php'">&times;</button>
        <h1>Create Product</h1>
        <form method="POST" action="create_product.php">
            <label for="product_name">Product Name</label>
            <input type="text" id="product_name" name="product_name" required>
            
            <div class="sub-product-fields">
                <div class="sub-product">
                    <label for="sub_product_1">Sub Product 1</label>
                    <input type="text" id="sub_product_1" name="sub_products[]" required>
                    <button type="button" class="remove-sub-product">&times;</button>
                </div>
            </div>

            <button type="button" class="add-sub-product"><i class="fas fa-plus"></i></button>
            
            <div class="submit-button">
                <input type="submit" value="Create Product">
                <button type="button" class="cancel-button" onclick="window.location.href='products.php'">Cancel</button>
            </div>
        </form>
    </div>
    <script>
        let subProductCount = 1;

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
