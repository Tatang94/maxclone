<?php
/**
 * Wallet Manager Class
 * Handles digital wallet operations for RideMax
 */

class WalletManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Get user wallet balance
     */
    public function getBalance($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return $result ? (float)$result['wallet_balance'] : 0.00;
        } catch (PDOException $e) {
            error_log("Error getting wallet balance: " . $e->getMessage());
            return 0.00;
        }
    }
    
    /**
     * Add money to wallet
     */
    public function addBalance($userId, $amount, $description = 'Top up') {
        if ($amount <= 0) {
            return false;
        }
        
        try {
            $this->pdo->beginTransaction();
            
            // Update user balance
            $stmt = $this->pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
            $stmt->execute([$amount, $userId]);
            
            // Record transaction
            $this->recordTransaction($userId, $amount, 'credit', $description);
            
            $this->pdo->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error adding wallet balance: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Deduct money from wallet
     */
    public function deductBalance($userId, $amount, $description = 'Payment') {
        if ($amount <= 0) {
            return false;
        }
        
        try {
            $this->pdo->beginTransaction();
            
            // Check current balance
            $currentBalance = $this->getBalance($userId);
            if ($currentBalance < $amount) {
                $this->pdo->rollBack();
                return false;
            }
            
            // Update user balance
            $stmt = $this->pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
            $stmt->execute([$amount, $userId]);
            
            // Record transaction
            $this->recordTransaction($userId, $amount, 'debit', $description);
            
            $this->pdo->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error deducting wallet balance: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Transfer money between wallets
     */
    public function transfer($fromUserId, $toUserId, $amount, $description = 'Transfer') {
        if ($amount <= 0 || $fromUserId == $toUserId) {
            return false;
        }
        
        try {
            $this->pdo->beginTransaction();
            
            // Check sender balance
            $senderBalance = $this->getBalance($fromUserId);
            if ($senderBalance < $amount) {
                $this->pdo->rollBack();
                return false;
            }
            
            // Deduct from sender
            $stmt = $this->pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
            $stmt->execute([$amount, $fromUserId]);
            
            // Add to receiver
            $stmt = $this->pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
            $stmt->execute([$amount, $toUserId]);
            
            // Record transactions
            $this->recordTransaction($fromUserId, $amount, 'debit', $description . ' - Sent');
            $this->recordTransaction($toUserId, $amount, 'credit', $description . ' - Received');
            
            $this->pdo->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error transferring wallet balance: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get wallet transaction history
     */
    public function getTransactionHistory($userId, $limit = 50) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM wallet_transactions 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting transaction history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Record wallet transaction
     */
    private function recordTransaction($userId, $amount, $type, $description) {
        try {
            // Check if wallet_transactions table exists, if not use payments table
            $tableExists = $this->pdo->query("
                SELECT COUNT(*) FROM information_schema.tables 
                WHERE table_name = 'wallet_transactions'
            ")->fetchColumn();
            
            if ($tableExists > 0) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO wallet_transactions (user_id, amount, type, description, created_at)
                    VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$userId, $amount, $type, $description]);
            } else {
                // Fallback to payments table
                $stmt = $this->pdo->prepare("
                    INSERT INTO payments (order_id, amount, payment_method, payment_status, transaction_id, created_at)
                    VALUES (0, ?, 'wallet', 'completed', ?, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$amount, $description]);
            }
        } catch (PDOException $e) {
            error_log("Error recording wallet transaction: " . $e->getMessage());
        }
    }
    
    /**
     * Create wallet transaction table if not exists
     */
    public function createTransactionTable() {
        try {
            global $databaseType;
            
            if ($databaseType === 'postgresql') {
                $sql = "
                CREATE TABLE IF NOT EXISTS wallet_transactions (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    type VARCHAR(10) NOT NULL CHECK (type IN ('credit', 'debit')),
                    description TEXT,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )";
            } else {
                $sql = "
                CREATE TABLE IF NOT EXISTS wallet_transactions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    type VARCHAR(10) NOT NULL CHECK (type IN ('credit', 'debit')),
                    description TEXT,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )";
            }
            
            $this->pdo->exec($sql);
            return true;
            
        } catch (PDOException $e) {
            error_log("Error creating wallet transactions table: " . $e->getMessage());
            return false;
        }
    }
}
?>