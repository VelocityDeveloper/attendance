const fs = require("fs");
const path = require("path");
const archiver = require("archiver");
const version = require("../package.json").version;
const name = require("../package.json").name;

// Buat folder `dist` jika belum ada
const distDir = path.join(__dirname, "../dist");
if (!fs.existsSync(distDir)) {
  fs.mkdirSync(distDir);
}

// Nama file ZIP yang akan dihasilkan
const output = fs.createWriteStream(
  path.join(distDir, `${name}-${version}.zip`)
);
const archive = archiver("zip", {
  zlib: { level: 9 }, // Tingkat kompresi maksimal
});

// Event listener untuk ketika file ZIP selesai dibuat
output.on("close", function () {
  console.log(
    "File ZIP berhasil dibuat: " + archive.pointer() + " total bytes"
  );
  console.log(
    `File ZIP disimpan di: ${path.join(distDir, `${name}-${version}.zip`)}`
  );
});

// Event listener untuk menangani error
archive.on("error", function (err) {
  throw err;
});

// Mulai proses kompresi
archive.pipe(output);

// Direktori root Laravel
const laravelDir = path.join(__dirname, ".."); // Sesuaikan dengan struktur direktori Anda

// Daftar file dan folder yang akan diabaikan
const ignoreList = [
  ".vscode",
  ".git",
  ".env",
  "package.json",
  "package-lock.json",
  "node_modules",
  "storage",
  "vendor",
  "dist",
  ".github",
  "composer.lock",
  "composer.json",
];

// Fungsi untuk menambahkan file dan folder ke ZIP
function addDirectoryToArchive(dir, prefix = "") {
  const files = fs.readdirSync(dir);

  files.forEach((file) => {
    const filePath = path.join(dir, file);
    const relativePath = prefix ? `${prefix}/${file}` : file;

    // Cek apakah file/folder harus diabaikan
    if (ignoreList.includes(file)) {
      console.log(`Mengabaikan: ${relativePath}`);
      return;
    }

    const stat = fs.statSync(filePath);

    if (stat.isDirectory()) {
      // Tambahkan direktori ke ZIP
      addDirectoryToArchive(filePath, relativePath);
    } else {
      // Tambahkan file ke ZIP
      archive.file(filePath, { name: relativePath });
      console.log(`Menambahkan: ${relativePath}`);
    }
  });
}

// Mulai proses menambahkan file dan folder ke ZIP
addDirectoryToArchive(laravelDir);

// Finalisasi proses kompresi
archive.finalize();
