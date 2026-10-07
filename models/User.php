<?php
/**
 * Istanbul University MIS Alumni Portal
 * In-Memory User Model
 *
 * Implements full CRUD operations for User entities without requiring
 * an external database connection. Data is stored in-memory, with
 * optional session persistence to retain state across web requests.
 */

class User {
    /**
     * Initial seed data representing Istanbul University MIS stakeholders.
     */
    private static array $defaultSeed = [
        [
            'id' => 1,
            'email' => 'admin@istanbul.edu.tr',
            'first_name' => 'Admin',
            'last_name' => 'MIS',
            'role' => 'super_admin',
            'is_verified' => 1,
            'created_at' => '2026-01-10 09:00:00',
            'updated_at' => '2026-01-10 09:00:00'
        ],
        [
            'id' => 2,
            'email' => 'chair.mis@istanbul.edu.tr',
            'first_name' => 'Department',
            'last_name' => 'Chair',
            'role' => 'faculty_admin',
            'is_verified' => 1,
            'created_at' => '2026-01-11 10:15:00',
            'updated_at' => '2026-01-11 10:15:00'
        ],
        [
            'id' => 3,
            'email' => 'alumni.mert@example.com',
            'first_name' => 'Mert',
            'last_name' => 'Yılmaz',
            'role' => 'alumni',
            'is_verified' => 1,
            'created_at' => '2026-01-15 11:30:00',
            'updated_at' => '2026-01-15 11:30:00'
        ],
        [
            'id' => 4,
            'email' => 'alumni.zeynep@example.com',
            'first_name' => 'Zeynep',
            'last_name' => 'Kaya',
            'role' => 'alumni',
            'is_verified' => 1,
            'created_at' => '2026-02-01 14:00:00',
            'updated_at' => '2026-02-01 14:00:00'
        ],
        [
            'id' => 5,
            'email' => 'student.ayse@ogr.iu.edu.tr',
            'first_name' => 'Ayşe',
            'last_name' => 'Öztürk',
            'role' => 'student',
            'is_verified' => 1,
            'created_at' => '2026-03-05 16:20:00',
            'updated_at' => '2026-03-05 16:20:00'
        ]
    ];

    /**
     * Static in-memory storage array.
     */
    private static ?array $storage = null;

    /**
     * Allowed user roles in the system.
     */
    public const ALLOWED_ROLES = ['super_admin', 'faculty_admin', 'alumni', 'student'];

    /**
     * Initialize and retrieve storage reference.
     * Synchronizes with PHP session if active for multi-request persistence.
     */
    private static function initStorage(): void {
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['in_memory_users'])) {
            self::$storage = $_SESSION['in_memory_users'];
            return;
        }

        if (self::$storage === null) {
            self::$storage = self::$defaultSeed;
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['in_memory_users'] = self::$storage;
            }
        }
    }

    /**
     * Persist storage state to session if available.
     */
    private static function syncStorage(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['in_memory_users'] = self::$storage;
        }
    }

    /**
     * Retrieve all users.
     *
     * @return array List of user records.
     */
    public static function all(): array {
        self::initStorage();
        return array_values(self::$storage);
    }

    /**
     * Find a single user by ID.
     *
     * @param int $id User ID.
     * @return array|null User record or null if not found.
     */
    public static function findById(int $id): ?array {
        self::initStorage();
        foreach (self::$storage as $user) {
            if ((int)$user['id'] === $id) {
                return $user;
            }
        }
        return null;
    }

    /**
     * Find a single user by email address.
     *
     * @param string $email User email.
     * @return array|null User record or null if not found.
     */
    public static function findByEmail(string $email): ?array {
        self::initStorage();
        $normalizedEmail = strtolower(trim($email));
        foreach (self::$storage as $user) {
            if (strtolower($user['email']) === $normalizedEmail) {
                return $user;
            }
        }
        return null;
    }

    /**
     * Create a new user record in memory.
     *
     * @param array $data Input fields (email, first_name, last_name, role, etc.).
     * @return array The newly created user record.
     * @throws InvalidArgumentException When validation fails.
     */
    public static function create(array $data): array {
        self::initStorage();

        // Validate required fields
        $email = trim($data['email'] ?? '');
        $firstName = trim($data['first_name'] ?? '');
        $lastName = trim($data['last_name'] ?? '');
        $role = trim($data['role'] ?? 'alumni');
        $isVerified = isset($data['is_verified']) ? (int)(bool)$data['is_verified'] : 0;

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('A valid email address is required.');
        }

        if (empty($firstName)) {
            throw new InvalidArgumentException('First name is required.');
        }

        if (empty($lastName)) {
            throw new InvalidArgumentException('Last name is required.');
        }

        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            throw new InvalidArgumentException('Invalid role specified. Allowed roles: ' . implode(', ', self::ALLOWED_ROLES));
        }

        // Check email uniqueness
        if (self::findByEmail($email) !== null) {
            throw new InvalidArgumentException('A user with this email address already exists.');
        }

        // Generate next auto-increment ID
        $maxId = 0;
        foreach (self::$storage as $user) {
            if ((int)$user['id'] > $maxId) {
                $maxId = (int)$user['id'];
            }
        }
        $newId = $maxId + 1;
        $now = date('Y-m-d H:i:s');

        $newUser = [
            'id' => $newId,
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role' => $role,
            'is_verified' => $isVerified,
            'created_at' => $now,
            'updated_at' => $now
        ];

        self::$storage[] = $newUser;
        self::syncStorage();

        return $newUser;
    }

    /**
     * Update an existing user by ID.
     *
     * @param int $id User ID.
     * @param array $data Fields to update.
     * @return array|null Updated user record or null if not found.
     * @throws InvalidArgumentException When validation fails.
     */
    public static function update(int $id, array $data): ?array {
        self::initStorage();

        $targetIndex = null;
        foreach (self::$storage as $index => $user) {
            if ((int)$user['id'] === $id) {
                $targetIndex = $index;
                break;
            }
        }

        if ($targetIndex === null) {
            return null;
        }

        $user = self::$storage[$targetIndex];

        // Validate and update email if provided
        if (isset($data['email'])) {
            $email = trim($data['email']);
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('A valid email address is required.');
            }
            // Ensure email uniqueness if changed
            $existing = self::findByEmail($email);
            if ($existing !== null && (int)$existing['id'] !== $id) {
                throw new InvalidArgumentException('A user with this email address already exists.');
            }
            $user['email'] = $email;
        }

        // Validate first name if provided
        if (isset($data['first_name'])) {
            $firstName = trim($data['first_name']);
            if (empty($firstName)) {
                throw new InvalidArgumentException('First name cannot be empty.');
            }
            $user['first_name'] = $firstName;
        }

        // Validate last name if provided
        if (isset($data['last_name'])) {
            $lastName = trim($data['last_name']);
            if (empty($lastName)) {
                throw new InvalidArgumentException('Last name cannot be empty.');
            }
            $user['last_name'] = $lastName;
        }

        // Validate role if provided
        if (isset($data['role'])) {
            $role = trim($data['role']);
            if (!in_array($role, self::ALLOWED_ROLES, true)) {
                throw new InvalidArgumentException('Invalid role specified. Allowed roles: ' . implode(', ', self::ALLOWED_ROLES));
            }
            $user['role'] = $role;
        }

        // Update verification status if provided
        if (isset($data['is_verified'])) {
            $user['is_verified'] = (int)(bool)$data['is_verified'];
        }

        $user['updated_at'] = date('Y-m-d H:i:s');
        self::$storage[$targetIndex] = $user;
        self::syncStorage();

        return $user;
    }

    /**
     * Delete a user by ID.
     *
     * @param int $id User ID.
     * @return bool True if deleted, false if user was not found.
     */
    public static function delete(int $id): bool {
        self::initStorage();

        foreach (self::$storage as $index => $user) {
            if ((int)$user['id'] === $id) {
                unset(self::$storage[$index]);
                self::$storage = array_values(self::$storage);
                self::syncStorage();
                return true;
            }
        }

        return false;
    }

    /**
     * Count total users in memory.
     *
     * @return int
     */
    public static function count(): int {
        self::initStorage();
        return count(self::$storage);
    }

    /**
     * Reset storage to initial default seed state.
     * Useful for automated testing.
     */
    public static function reset(): void {
        self::$storage = self::$defaultSeed;
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['in_memory_users'] = self::$defaultSeed;
        }
    }
}
