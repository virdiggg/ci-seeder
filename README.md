# Create seeder for CodeIgniter 3 from already existing table.

#### Inspired from [orangehill/iseed](https://github.com/orangehill/iseed) laravel.

- The library seeder is `application/libraries/Seeder.php`. This must be loaded on your controller.

- The example controller is `application/controllers/App.php`.

#### How to create Seeder file: `php index.php <your controller name> <your function name> "tablename"`.
```
cd c:/xampp/htdocs/codeigniter && php index.php app seed "users"
```
#### How to use Controller file: `php index.php <your controller name> <your function name> <filename> [--args]`.
- Add `--r` to generate resources. Optional.
```
cd c:/xampp/htdocs/codeigniter && php index.php app controller Admin/Dashboard/Table --r
```
#### How to use Model file: `php index.php <your controller name> <your function name> <filename> [--args]`.
- Add `--r` to generate resources. Optional.
- Add `--c` to generate its controller file as well. Optional.
```
cd c:/xampp/htdocs/codeigniter && php index.php app model Admin/Dashboard --r --c
```