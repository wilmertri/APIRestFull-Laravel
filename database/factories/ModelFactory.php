<?php

use Faker\Generator as Faker;
use App\User;
use App\Category;
use App\Product;
use App\Transaction;
use App\Seller;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(User::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', // secret
        'remember_token' => str_random(10),
        'verified' => $verificado = $faker->randomElement([User::USUARIO_VERIFICADO, User::USUARIO_NO_VERIFICADO]),
        'verification_token' => $verificado == User::USUARIO_VERIFICADO ? null : User::generarVerificationToken(),
        'admin' => $verificado = $faker->randomElement([User::USUARIO_ADMINISTRADOR, User::USUARIO_REGULAR]),
    ];
});

$factory->define(Category::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'description' => $faker->paragraph(1),
    ];
});

$factory->define(Product::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'description' => $faker->paragraph(1),
        'quantity' => $faker->numberBetween(1, 10),
        'status' => $faker->randomElement([Product::PRODUCTO_DISPONIBLE, Product::PRODUCTO_NO_DISPONIBLE]),
        'image' => $faker->randomElement(['product1.png', 'product2.png', 'product3.png']),
        'seller_id' => User::all()->random()->id,
        //'seller_id' => User::inRandomOrder()->first()->id
    ];
});

$factory->define(Transaction::class, function (Faker $faker) {
    
	$vendedor = Seller::has('products')->get()->random();
	$comprador = User::all()->except($vendedor->id)->random();

    return [
        'quantity' => $faker->numberBetween(1, 10),
        'buyer_id' => $comprador->id,
        'product_id' => $vendedor->products->random()->id, 
    ];
});