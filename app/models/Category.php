<?php
class Category 
{
    private $conn;
    private $table = 'categories';

    public function __construct() 
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Get all categories
    public function getAllCategories() 
    {
        $query = "SELECT * FROM {$this->table} ORDER BY category ASC";
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get category by ID
    public function getCategoryById($categoryID) 
    {
        $query = "SELECT * FROM {$this->table} WHERE categoryID = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$categoryID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get category by name
    public function getCategoryByName($categoryName) 
    {
        $query = "SELECT * FROM {$this->table} WHERE category = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$categoryName]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Create new category
    public function createCategory($categoryName) 
    {
        // Check if category already exists
        if ($this->getCategoryByName($categoryName)) {
            return [
                'success' => false,
                'message' => 'Category already exists'
            ];
        }

        $query = "INSERT INTO {$this->table} (category) VALUES (?)";
        
        try {
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$categoryName]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Category created successfully',
                    'categoryID' => $this->conn->lastInsertId()
                ];
            }
        } catch (PDOException $e) {
            error_log("Create category error: " . $e->getMessage());
        }
        
        return [
            'success' => false,
            'message' => 'Failed to create category'
        ];
    }

    // Update category
    public function updateCategory($categoryID, $newName) 
    {
        $query = "UPDATE {$this->table} SET category = ? WHERE categoryID = ?";
        
        try {
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$newName, $categoryID]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Category updated successfully'
                ];
            }
        } catch (PDOException $e) {
            error_log("Update category error: " . $e->getMessage());
        }
        
        return [
            'success' => false,
            'message' => 'Failed to update category'
        ];
    }

    // Delete category
    public function deleteCategory($categoryID) 
    {
        // Check if category has products
        $checkQuery = "SELECT COUNT(*) as count FROM products WHERE categoryID = ?";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->execute([$categoryID]);
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            return [
                'success' => false,
                'message' => 'Cannot delete category with existing products'
            ];
        }

        $query = "DELETE FROM {$this->table} WHERE categoryID = ?";
        
        try {
            $stmt = $this->conn->prepare($query);
            $deleteResult = $stmt->execute([$categoryID]);
            
            if ($deleteResult) {
                return [
                    'success' => true,
                    'message' => 'Category deleted successfully'
                ];
            }
        } catch (PDOException $e) {
            error_log("Delete category error: " . $e->getMessage());
        }
        
        return [
            'success' => false,
            'message' => 'Failed to delete category'
        ];
    }

    // Get product count by category
    public function getProductCountByCategory($categoryID) 
    {
        $query = "SELECT COUNT(*) as count FROM products 
                 WHERE categoryID = ? AND is_available = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$categoryID]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
}
