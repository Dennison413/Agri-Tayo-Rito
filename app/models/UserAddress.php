<?php
// app/models/UserAddress.php
// User Address Management Model
require_once __DIR__ . '/../../config/database.php';

class UserAddress 
{
    private $conn;
    private $table = 'user_addresses';
    
    public function __construct() 
    {
        $database = new Database();
        $this->conn = $database->connect();
    }
    
    // Get all addresses for a user
    public function getUserAddresses($userID) 
    {
        $query = "SELECT * FROM {$this->table} 
                  WHERE userID = :userID 
                  ORDER BY created_at DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting user addresses: " . $e->getMessage());
            return [];
        }
    }
    
    // Get a specific address (with user verification)
    public function getAddress($addressID, $userID = null) 
    {
        if ($userID !== null) {
            // Verify ownership
            $query = "SELECT * FROM {$this->table} 
                      WHERE addressID = :addressID AND userID = :userID 
                      LIMIT 1";
            
            try {
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':addressID', $addressID, PDO::PARAM_INT);
                $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
                $stmt->execute();
                
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Error getting address: " . $e->getMessage());
                return null;
            }
        } else {
            // No user verification (for admin purposes)
            $query = "SELECT * FROM {$this->table} 
                      WHERE addressID = :addressID 
                      LIMIT 1";
            
            try {
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':addressID', $addressID, PDO::PARAM_INT);
                $stmt->execute();
                
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Error getting address: " . $e->getMessage());
                return null;
            }
        }
    }
    
    // Get the most recent address (used as default)
    public function getDefaultAddress($userID) 
    {
        $query = "SELECT * FROM {$this->table} 
                  WHERE userID = :userID 
                  ORDER BY created_at DESC 
                  LIMIT 1";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting default address: " . $e->getMessage());
            return null;
        }
    }
    
    // Add new address
    public function addAddress($userID, $address, $municipality, $province, $postal_code) 
    {
        $query = "INSERT INTO {$this->table} 
                  (userID, address, municipality, province, postal_code) 
                  VALUES (:userID, :address, :municipality, :province, :postal_code)";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':municipality', $municipality);
            $stmt->bindParam(':province', $province);
            $stmt->bindParam(':postal_code', $postal_code);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Address added successfully',
                    'addressID' => $this->conn->lastInsertId()
                ];
            }
        } catch (PDOException $e) {
            error_log("Error adding address: " . $e->getMessage());
        }
        
        return [
            'success' => false,
            'message' => 'Failed to add address'
        ];
    }
    
    // Update address (with user verification)
    public function updateAddress($addressID, $userID, $address, $municipality, $province, $postal_code) 
    {
        $query = "UPDATE {$this->table} 
                  SET address = :address, 
                      municipality = :municipality, 
                      province = :province, 
                      postal_code = :postal_code
                  WHERE addressID = :addressID AND userID = :userID";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':municipality', $municipality);
            $stmt->bindParam(':province', $province);
            $stmt->bindParam(':postal_code', $postal_code);
            $stmt->bindParam(':addressID', $addressID, PDO::PARAM_INT);
            $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    return [
                        'success' => true,
                        'message' => 'Address updated successfully'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Address not found or no changes made'
                    ];
                }
            }
        } catch (PDOException $e) {
            error_log("Error updating address: " . $e->getMessage());
        }
        
        return [
            'success' => false,
            'message' => 'Failed to update address'
        ];
    }
    
    // Delete address (with user verification)
    public function deleteAddress($addressID, $userID) 
    {
        $query = "DELETE FROM {$this->table} 
                  WHERE addressID = :addressID AND userID = :userID";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':addressID', $addressID, PDO::PARAM_INT);
            $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    return [
                        'success' => true,
                        'message' => 'Address deleted successfully'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Address not found'
                    ];
                }
            }
        } catch (PDOException $e) {
            error_log("Error deleting address: " . $e->getMessage());
        }
        
        return [
            'success' => false,
            'message' => 'Failed to delete address'
        ];
    }
    
    // Count addresses for a user
    public function countUserAddresses($userID) 
    {
        $query = "SELECT COUNT(*) as count FROM {$this->table} 
                  WHERE userID = :userID";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':userID', $userID, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['count'] : 0;
        } catch (PDOException $e) {
            error_log("Error counting addresses: " . $e->getMessage());
            return 0;
        }
    }
    
    // Check if user has any addresses
    public function hasAddresses($userID) 
    {
        return $this->countUserAddresses($userID) > 0;
    }
    
    // Format address for display
    public function formatAddress($addressData) 
    {
        if (!$addressData) {
            return '';
        }
        
        return $addressData['address'] . ', ' . 
               $addressData['municipality'] . ', ' . 
               $addressData['province'] . ' ' . 
               $addressData['postal_code'];
    }
    
    // Get address for order (used in checkout)
    public function getAddressForOrder($addressID, $userID) 
    {
        $address = $this->getAddress($addressID, $userID);
        
        if ($address) {
            return [
                'address' => $address['address'],
                'municipality' => $address['municipality'],
                'province' => $address['province'],
                'postal_code' => $address['postal_code']
            ];
        }
        
        return null;
    }
}