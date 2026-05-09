<?php
declare(strict_types=1);

namespace App\Models;

use App\Database\Connection;
use PDO;

/**
 * Model administratora. Operuje bezpośrednio na PDO - bez ORM.
 */
final class Admin
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly string $name,
        public readonly string $createdAt,
    ) {
    }

    public static function findByEmail(string $email): ?self
    {
        $pdo = Connection::get();
        $stmt = $pdo->prepare('SELECT id, email, name, created_at FROM admins WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => strtolower(trim($email))]);
        $row = $stmt->fetch();
        return $row ? self::fromRow($row) : null;
    }

    public static function findById(int $id): ?self
    {
        $pdo = Connection::get();
        $stmt = $pdo->prepare('SELECT id, email, name, created_at FROM admins WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? self::fromRow($row) : null;
    }

    /**
     * Tworzy nowe konto admina. Nazwa default = local-part emaila przed @.
     */
    public static function create(string $email, ?string $name = null): self
    {
        $email = strtolower(trim($email));
        if ($name === null || trim($name) === '') {
            $local = substr($email, 0, (int) strpos($email, '@'));
            $name = ucfirst($local !== '' ? $local : 'Admin');
        }
        $pdo = Connection::get();
        $stmt = $pdo->prepare('INSERT INTO admins (email, name) VALUES (:email, :name)');
        $stmt->execute(['email' => $email, 'name' => $name]);
        $id = (int) $pdo->lastInsertId();
        $found = self::findById($id);
        if ($found === null) {
            throw new \RuntimeException('Nie udało się utworzyć konta admina.');
        }
        return $found;
    }

    /**
     * Znajdz lub utworz konto - dla auto-rejestracji magic link'iem.
     */
    public static function findOrCreate(string $email): self
    {
        $existing = self::findByEmail($email);
        return $existing ?? self::create($email);
    }

    /**
     * @param array<string,mixed> $row
     */
    private static function fromRow(array $row): self
    {
        return new self(
            id:        (int) $row['id'],
            email:     (string) $row['email'],
            name:      (string) $row['name'],
            createdAt: (string) $row['created_at'],
        );
    }
}
