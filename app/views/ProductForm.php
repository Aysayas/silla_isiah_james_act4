<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

// Editing when a product (with an id) was passed in; creating otherwise.
$is_edit = isset($product['id']);
$action  = $is_edit ? '/products/update/' . $product['id'] : '/products/store';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit Product' : 'Add Product'; ?></title>
</head>
<body>
    <h1><?php echo $is_edit ? 'Edit Product' : 'Add Product'; ?></h1>

    <?php if (!empty($errors)): ?>
        <ul style="color:red;">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post" action="<?php echo $action; ?>">
        <p>
            <label for="product_name">Product Name</label><br>
            <input type="text" id="product_name" name="product_name"
                   value="<?php echo htmlspecialchars($product['product_name'] ?? ''); ?>">
        </p>
        <p>
            <label for="description">Description</label><br>
            <textarea id="description" name="description"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
        </p>
        <p>
            <label for="price">Price</label><br>
            <input type="text" id="price" name="price"
                   value="<?php echo htmlspecialchars($product['price'] ?? ''); ?>">
        </p>
        <p>
            <label for="quantity">Quantity</label><br>
            <input type="text" id="quantity" name="quantity"
                   value="<?php echo htmlspecialchars($product['quantity'] ?? ''); ?>">
        </p>
        <p>
            <button type="submit"><?php echo $is_edit ? 'Update' : 'Create'; ?></button>
            <a href="/products">Cancel</a>
        </p>
    </form>
</body>
</html>
