# Skola

## Filament Shield

Custom implementation untuk manajemen Role & Permission di FilamentPHP + Spatie Laravel Permission.

---

### Struktur File

```
app/
├── Console/Commands/
│   └── GeneratePermissions.php     ← Artisan command auto-generate permissions
├── Filament/
│   ├── Resources/
│   │   ├── RoleResource.php        ← CRUD roles + assign permissions
│   │   ├── RoleResource/Pages/
│   │   │   ├── ListRoles.php
│   │   │   ├── CreateRole.php      ← sync permissions after create
│   │   │   └── EditRole.php        ← sync permissions after update
│   │   ├── PermissionResource.php  ← CRUD permissions
│   │   └── PermissionResource/Pages/
│   │       ├── ListPermissions.php
│   │       ├── CreatePermission.php
│   │       └── EditPermission.php
│   └── Traits/
│       └── HasShieldAuthorization.php  ← Pasang di Resource lain
database/seeders/
└── RolePermissionSeeder.php        ← Seed roles & permissions awal
```

---

### Setup

#### 1. Copy file ke project

Salin semua file ke lokasi yang sesuai di project Laravel Anda.

#### 2. Register Artisan Command

Di `app/Console/Kernel.php` (Laravel 10) atau otomatis di-discover (Laravel 11+):

```php
protected $commands = [
    \App\Console\Commands\GeneratePermissions::class,
];
```

#### 3. Generate permissions awal

```bash
# Preview dulu
php artisan shield:generate --dry-run

# Simpan ke database
php artisan shield:generate
```

#### 4. Seed roles & permissions

```bash
php artisan db:seed --class=RolePermissionSeeder
```

---

### Penggunaan di Resource Lain

Pasang trait `HasShieldAuthorization` di setiap Resource Filament Anda:

```php
use App\Filament\Traits\HasShieldAuthorization;

class UserResource extends Resource
{
    use HasShieldAuthorization;

    // ... kode lainnya
}
```

Trait ini otomatis mengecek permission berdasarkan nama model:

- `UserResource` → cek `view_any_users`, `create_users`, dst.
- `BlogPostResource` → cek `view_any_blog_posts`, dst.

#### Override nama resource (opsional)

```php
class PostResource extends Resource
{
    use HasShieldAuthorization;

    // Jika nama permission berbeda dari nama model
    protected static ?string $permissionResource = 'articles';
}
```

---

### Konvensi Penamaan Permission

| Permission           | Digunakan saat               |
| -------------------- | ---------------------------- |
| `view_any_users`     | Bisa lihat list user         |
| `view_users`         | Bisa lihat detail user       |
| `create_users`       | Bisa create user baru        |
| `update_users`       | Bisa edit user               |
| `delete_users`       | Bisa soft delete user        |
| `force_delete_users` | Bisa permanently delete user |
| `restore_users`      | Bisa restore user            |

---

### Menambah Resource Baru

1. **Tambahkan di `GeneratePermissions.php`:**

```php
protected array $resources = [
    // ... yang sudah ada ...
    'posts' => ['view_any', 'view', 'create', 'update', 'delete'],
];
```

2. **Jalankan command:**

```bash
php artisan shield:generate --resource=posts
```

3. **Pasang trait di Resource:**

```php
class PostResource extends Resource
{
    use HasShieldAuthorization;
}
```

---

### Super Admin

User dengan role `super-admin` otomatis bypass semua permission check.
Nama role bisa diubah di trait:

```php
// Dalam Resource Anda:
protected static string $superAdminRole = 'super-admin'; // default
```

Atau ubah global di `HasShieldAuthorization.php`.

---

### Kustomisasi Pengelompokan Permission di Form Role

Logika pengelompokan permission berada di `RoleResource::buildPermissionMatrix()`.
Ubah sesuai kebutuhan, misalnya grouping berdasarkan modul:

```php
$grouped = $permissions->groupBy(function ($permission) {
    // Contoh: group berdasarkan kata terakhir
    return Str::afterLast($permission->name, '_');
});
```
