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
            'title' => 'Manajemen Pengguna',
            'pageTitle' => 'Manajemen Pengguna',
            'users' => $users,
            'extraCss' => 'reports.css',
        ]);
    }

    /**
     * GET /users/create — Form pembuatan pengguna baru.
     */
    public function create(): void
    {
        Auth::checkRole('pemilik');

        $this->view('users/create', [
            'title' => 'Tambah Pengguna',
            'pageTitle' => 'Tambah Pengguna',
        ]);
    }

    /**
     * POST /users/store — Simpan pengguna baru.
     */
    public function store(): void
    {
        Auth::checkRole('pemilik');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/users');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'kasir';

        // Validasi
        $errors = [];
        if (empty($name))
            $errors[] = 'Nama wajib diisi.';
        if (empty($email))
            $errors[] = 'Email wajib diisi.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors[] = 'Format email tidak valid.';
        if (strlen($password) < 8)
            $errors[] = 'Password minimal 8 karakter.';
        if (!in_array($role, ['pemilik', 'kasir']))
            $errors[] = 'Role tidak valid.';

        if ($this->userModel->isEmailTaken($email)) {
            $errors[] = 'Email sudah digunakan.';
        }

        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode(' ', $errors)];
            $_SESSION['old_input'] = ['name' => $name, 'email' => $email, 'role' => $role];
            header('Location: ' . APP_URL . '/users/create');
            exit;
        }

        $this->userModel->createUser([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pengguna berhasil ditambahkan.'];
        header('Location: ' . APP_URL . '/users');
        exit;
    }

    /**
     * GET /users/edit?id=X — Form edit pengguna.
     */
    public function edit(): void
    {
        Auth::checkRole('pemilik');

        $id = (int)($_GET['id'] ?? 0);
        $user = $this->userModel->findById($id);

        if (!$user) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Pengguna tidak ditemukan.'];
            header('Location: ' . APP_URL . '/users');
            exit;
        }

        $this->view('users/edit', [
            'title' => 'Edit Pengguna',
            'pageTitle' => 'Edit Pengguna',
            'user' => $user,
        ]);
    }

    /**
     * POST /users/update — Update data pengguna.
     */
    public function update(): void
    {
        Auth::checkRole('pemilik');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/users');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'kasir';
        $password = $_POST['password'] ?? '';

        $user = $this->userModel->findById($id);
        if (!$user) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Pengguna tidak ditemukan.'];
            header('Location: ' . APP_URL . '/users');
            exit;
        }

        // Validasi
        $errors = [];
        if (empty($name))
            $errors[] = 'Nama wajib diisi.';
        if (empty($email))
            $errors[] = 'Email wajib diisi.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            $errors[] = 'Format email tidak valid.';
        if (!in_array($role, ['pemilik', 'kasir']))
            $errors[] = 'Role tidak valid.';

        if ($this->userModel->isEmailTaken($email, $id)) {
            $errors[] = 'Email sudah digunakan pengguna lain.';
        }

        // Password opsional saat edit (hanya jika diisi)
        if (!empty($password) && strlen($password) < 8) {
            $errors[] = 'Password minimal 8 karakter.';
        }

        if (!empty($errors)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => implode(' ', $errors)];
            header('Location: ' . APP_URL . '/users/edit?id=' . $id);
            exit;
        }

        // Update data
        $this->userModel->updateUser($id, [
            'name' => $name,
            'email' => $email,
            'role' => $role,
        ]);

        // Update password jika diisi
        if (!empty($password)) {
            $this->userModel->updatePassword($id, $password);
        }

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data pengguna berhasil diperbarui.'];
        header('Location: ' . APP_URL . '/users');
        exit;
    }

    /**
     * POST /users/delete — Nonaktifkan pengguna (soft delete).
     */
    public function delete(): void
    {
        Auth::checkRole('pemilik');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/users');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);

        // Jangan bisa nonaktifkan diri sendiri
        if ($id === (int)Auth::user('id')) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Tidak dapat menonaktifkan akun sendiri.'];
            header('Location: ' . APP_URL . '/users');
            exit;
        }

        $user = $this->userModel->findById($id);
        if (!$user) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Pengguna tidak ditemukan.'];
            header('Location: ' . APP_URL . '/users');
            exit;
        }

        $this->userModel->deactivate($id);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pengguna "' . $user['name'] . '" berhasil dinonaktifkan.'];
        header('Location: ' . APP_URL . '/users');
        exit;
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

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/users/inactive');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $user = $this->userModel->findById($id);

        if (!$user) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Pengguna tidak ditemukan.'];
            header('Location: ' . APP_URL . '/users/inactive');
            exit;
        }

        $this->userModel->activate($id);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pengguna "' . $user['name'] . '" berhasil diaktifkan kembali.'];
        header('Location: ' . APP_URL . '/users');
        exit;
    }
}
