<?php
/**
 * app/controllers/UserController.php
 * CRUD manajemen pengguna — hanya bisa diakses oleh role 'pemilik'.
 */

require_once APP_PATH . '/models/User.php';

class UserController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * GET /users — Daftar semua pengguna aktif.
     */
    public function index(): void
    {
        Auth::checkRole('pemilik');

        $users = $this->userModel->getAllActive();

        $this->view('users/index', [
            'title'     => 'Manajemen Pengguna',
            'pageTitle' => 'Manajemen Pengguna',
            'users'     => $users,
            'extraCss'  => 'reports.css',
        ]);
    }

    /**
     * GET /users/create — Form pembuatan pengguna baru.
     */
    public function create(): void
    {
        Auth::checkRole('pemilik');

        $this->view('users/create', [
            'title'     => 'Tambah Pengguna',
            'pageTitle' => 'Tambah Pengguna',
        ]);
    }

    /**
     * POST /users/store — Simpan pengguna baru.
     */
    public function store(): void
    {
        Auth::checkRole('pemilik');
        Csrf::verify();

        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 'kasir';

        // Validasi
        $errors = $this->validateUserInput($name, $email, $role, $password, true);

        if ($this->userModel->isEmailTaken($email)) {
            $errors[] = 'Email sudah digunakan.';
        }

        if (!empty($errors)) {
            $_SESSION['old_input'] = ['name' => $name, 'email' => $email, 'role' => $role];
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/users/create');
        }

        $this->userModel->createUser([
            'name'     => $name,
            'email'    => $email,
            'password' => $password,
            'role'     => $role,
        ]);

        $this->flash('success', 'Pengguna berhasil ditambahkan.');
        $this->redirect('/users');
    }

    /**
     * GET /users/edit?id=X — Form edit pengguna.
     */
    public function edit(): void
    {
        Auth::checkRole('pemilik');

        $id   = (int) ($_GET['id'] ?? 0);
        $user = $this->userModel->findById($id);

        if (!$user) {
            $this->flash('error', 'Pengguna tidak ditemukan.');
            $this->redirect('/users');
        }

        $this->view('users/edit', [
            'title'     => 'Edit Pengguna',
            'pageTitle' => 'Edit Pengguna',
            'user'      => $user,
        ]);
    }

    /**
     * POST /users/update — Update data pengguna.
     */
    public function update(): void
    {
        Auth::checkRole('pemilik');
        Csrf::verify();

        $id       = (int) ($_POST['id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $role     = $_POST['role'] ?? 'kasir';
        $password = $_POST['password'] ?? '';

        $user = $this->userModel->findById($id);
        if (!$user) {
            $this->flash('error', 'Pengguna tidak ditemukan.');
            $this->redirect('/users');
        }

        // Validasi (password opsional saat edit)
        $errors = $this->validateUserInput($name, $email, $role, $password, false);

        if ($this->userModel->isEmailTaken($email, $id)) {
            $errors[] = 'Email sudah digunakan pengguna lain.';
        }

        if (!empty($errors)) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/users/edit?id=' . $id);
        }

        // Update data
        $this->userModel->updateUser($id, [
            'name'  => $name,
            'email' => $email,
            'role'  => $role,
        ]);

        // Update password jika diisi
        if (!empty($password)) {
            $this->userModel->updatePassword($id, $password);
        }

        $this->flash('success', 'Data pengguna berhasil diperbarui.');
        $this->redirect('/users');
    }

    /**
     * POST /users/delete — Nonaktifkan pengguna (soft delete).
     */
    public function delete(): void
    {
        Auth::checkRole('pemilik');
        Csrf::verify();

        $id = (int) ($_POST['id'] ?? 0);

        // Jangan bisa nonaktifkan diri sendiri
        if ($id === (int) Auth::user('id')) {
            $this->flash('error', 'Tidak dapat menonaktifkan akun sendiri.');
            $this->redirect('/users');
        }

        $user = $this->userModel->findById($id);
        if (!$user) {
            $this->flash('error', 'Pengguna tidak ditemukan.');
            $this->redirect('/users');
        }

        $this->userModel->deactivate($id);

        $this->flash('success', 'Pengguna "' . $user['name'] . '" berhasil dinonaktifkan.');
        $this->redirect('/users');
    }

    /**
     * GET /users/inactive — Daftar pengguna nonaktif.
     */
    public function inactive(): void
    {
        Auth::checkRole('pemilik');

        $users = $this->userModel->getAllInactive();

        $this->view('users/inactive', [
            'title'     => 'Pengguna Nonaktif',
            'pageTitle' => 'Pengguna Nonaktif',
            'users'     => $users,
            'extraCss'  => 'reports.css',
        ]);
    }

    /**
     * POST /users/activate — Aktifkan kembali pengguna.
     */
    public function activate(): void
    {
        Auth::checkRole('pemilik');
        Csrf::verify();

        $id   = (int) ($_POST['id'] ?? 0);
        $user = $this->userModel->findById($id);

        if (!$user) {
            $this->flash('error', 'Pengguna tidak ditemukan.');
            $this->redirect('/users/inactive');
        }

        $this->userModel->activate($id);

        $this->flash('success', 'Pengguna "' . $user['name'] . '" berhasil diaktifkan kembali.');
        $this->redirect('/users');
    }

    // ── Validasi ─────────────────────────────────────────────────────────────

    /**
     * Validasi input pengguna.
     *
     * @param bool $isNew True jika tambah baru (password wajib), false jika edit (password opsional)
     */
    private function validateUserInput(string $name, string $email, string $role, string $password, bool $isNew): array
    {
        $errors = [];

        if (empty($name)) {
            $errors[] = 'Nama wajib diisi.';
        }
        if (empty($email)) {
            $errors[] = 'Email wajib diisi.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid.';
        }
        if (!in_array($role, ['pemilik', 'kasir'])) {
            $errors[] = 'Role tidak valid.';
        }

        // Password: wajib saat tambah baru, opsional saat edit
        if ($isNew && strlen($password) < 8) {
            $errors[] = 'Password minimal 8 karakter.';
        }
        if (!$isNew && !empty($password) && strlen($password) < 8) {
            $errors[] = 'Password minimal 8 karakter.';
        }

        return $errors;
    }
}
