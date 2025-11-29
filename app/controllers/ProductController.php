<?php
// app/controllers/ProductController.php

require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/User.php'; // If seller validation is needed

class ProductController
{
    private $productModel;
    private $categoryModel;
    private $reviewModel;

    public function __construct()
    {
        $this->productModel = new Product();
        $this->categoryModel = new Category();
        $this->reviewModel = new Review();
    }

    // ======================================
    // CATEGORY MANAGEMENT
    // ======================================

    public function categories()
    {
        $categories = $this->categoryModel->getAllCategories();

        include __DIR__ . '/../../views/products/categories.php';
    }

    public function getCategoriesJSON()
    {
        echo json_encode($this->categoryModel->getAllCategories());
    }

    // ======================================
    // MARKETPLACE PRODUCT LISTING
    // ======================================

    public function index()
    {
        $filters = [
            'categoryID' => $_GET['categoryID'] ?? null,
            'search'     => $_GET['search']     ?? null,
            'min_price'  => $_GET['min_price']  ?? null,
            'max_price'  => $_GET['max_price']  ?? null,
            'sort'       => $_GET['sort']       ?? 'newest',
            'in_stock'   => $_GET['in_stock']   ?? null,
        ];

        $limit  = $_GET['limit']  ?? 20;
        $offset = $_GET['offset'] ?? 0;

        $products = $this->productModel->getAllProducts($filters, $limit, $offset);
        $categories = $this->categoryModel->getAllCategories();

        include __DIR__ . '/../../views/products/index.php';
    }

    public function indexJSON()
    {
        $filters = [
            'categoryID' => $_GET['categoryID'] ?? null,
            'search'     => $_GET['search']     ?? null,
            'min_price'  => $_GET['min_price']  ?? null,
            'max_price'  => $_GET['max_price']  ?? null,
            'sort'       => $_GET['sort']       ?? 'newest',
            'in_stock'   => $_GET['in_stock']   ?? null,
        ];

        $limit  = $_GET['limit']  ?? 20;
        $offset = $_GET['offset'] ?? 0;

        echo json_encode(
            $this->productModel->getAllProducts($filters, $limit, $offset)
        );
    }

    // ======================================
    // PRODUCT DETAILS PAGE
    // ======================================

    public function details()
    {
        $productID = $_GET['id'];

        $product = $this->productModel->getProductById($productID);

        if (!$product) {
            die("Product not found");
        }

        include __DIR__ . '/../../views/products/details.php';
    }

    public function detailsJSON()
    {
        $productID = $_GET['id'];

        echo json_encode($this->productModel->getProductById($productID));
    }

    // ======================================
    // SELLER PRODUCT MANAGEMENT
    // ======================================

    public function sellerProducts()
    {
        $sellerID = $_SESSION['sellerID'];

        $products = $this->productModel->getProductsBySeller($sellerID, true);
        $categories = $this->categoryModel->getAllCategories();

        include __DIR__ . '/../../views/seller/products.php';
    }

    public function createProduct()
    {
        $sellerID = $_SESSION['sellerID'];

        $data = [
            'categoryID'       => $_POST['categoryID'],
            'product_name'     => $_POST['product_name'],
            'description'      => $_POST['description'] ?? null,
            'price'            => $_POST['price'],
            'stock_quantity'   => $_POST['stock_quantity'],
            'unit'             => $_POST['unit'],
            'is_available'     => $_POST['is_available'] ?? 1,
            'low_stock_threshold' => $_POST['low_stock_threshold'] ?? 5,
        ];

        $result = $this->productModel->createProduct($sellerID, $data);

        echo json_encode($result);
    }

    public function updateProduct()
    {
        $sellerID = $_SESSION['sellerID'];
        $productID = $_POST['productID'];

        $data = $_POST;

        $result = $this->productModel->updateProduct($productID, $sellerID, $data);

        echo json_encode($result);
    }

    public function deleteProduct()
    {
        $sellerID = $_SESSION['sellerID'];
        $productID = $_POST['productID'];

        $result = $this->productModel->deleteProduct($productID, $sellerID);

        echo json_encode($result);
    }

    // ======================================
    // STOCK MANAGEMENT
    // ======================================

    public function updateStock()
    {
        $sellerID = $_SESSION['sellerID'];
        $productID = $_POST['productID'];
        $quantity  = $_POST['newQuantity'];

        $result = $this->productModel->updateStock($productID, $sellerID, $quantity);

        echo json_encode($result);
    }

    public function lowStock()
    {
        $sellerID = $_SESSION['sellerID'];

        echo json_encode($this->productModel->getLowStockProducts($sellerID));
    }

    // ======================================
    // IMAGE MANAGEMENT
    // ======================================

    public function addImage()
    {
        $sellerID = $_SESSION['sellerID'];
        $productID = $_POST['productID'];
        $imagePath = $_POST['image_path'];
        $imageOrder = $_POST['image_order'] ?? 1;

        $result = $this->productModel->addProductImage($productID, $sellerID, $imagePath, $imageOrder);

        echo json_encode($result);
    }

    public function deleteImage()
    {
        $sellerID = $_SESSION['sellerID'];
        $imageID = $_POST['imageID'];

        $result = $this->productModel->deleteProductImage($imageID, $sellerID);

        echo json_encode($result);
    }

    // ======================================
    // REVIEWS
    // ======================================

    public function submitReview()
    {
        $buyerID = $_SESSION['userID'];

        $data = [
            'productID'   => $_POST['productID'],
            'orderID'     => $_POST['orderID'],
            'rating'      => $_POST['rating'],
            'review_text' => $_POST['review_text'] ?? null,
        ];

        $result = $this->reviewModel->createReview($buyerID, $data);

        echo json_encode($result);
    }

    public function editReview()
    {
        $buyerID  = $_SESSION['userID'];
        $reviewID = $_POST['reviewID'];

        $data = $_POST;

        $result = $this->reviewModel->updateReview($reviewID, $buyerID, $data);

        echo json_encode($result);
    }

    public function deleteReview()
    {
        $buyerID  = $_SESSION['userID'];
        $reviewID = $_POST['reviewID'];

        $result = $this->reviewModel->deleteReview($reviewID, $buyerID);

        echo json_encode($result);
    }

    public function reviewStats()
    {
        $productID = $_GET['productID'];

        echo json_encode($this->productModel->getReviewStats($productID));
    }
}
