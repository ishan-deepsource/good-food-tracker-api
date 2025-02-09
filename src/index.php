<?php

	/**
	 * Author: Igor Ilić <github@igorilic.net>
	 * Date: 2021-08-08
	 * Project: Good Food Tracker - API
	 */

	/**
	 * Swagger doc block
	 *
	 * @OA\Info(
	 *   title="Good Food Tracker API",
	 *   version="1.0.0",
	 *     description="The project aims to allow the users to take pictures and/or leave notes, ratings, comments about restaurants they visit
	in order to be able to reference it later when they try to pick were they want to go eat out or order from.",
	 *   @OA\Contact(
	 *     name="Igor Ilic",
	 *     email="github@igorilic.net"
	 *   ),
	 * )
	 *
	 * @OA\Server (url=DEV_API_URL, description="Development API base url")
	 * @OA\Server (url=PROD_API_URL, description="Production API base url")
	 *
	 * @OA\Components(
	 *         @OA\SecurityScheme(
	 *             securityScheme="bearer",
	 *             type="http",
	 *             scheme="bearer",
	 *         ),
	 *     )
	 *
	 *
	 * @OA\Schema (
	 *     schema="error_response",
	 *      type="object",
	 *      properties={
	 *			@OA\Property(property="class", type="string"),
	 *     		@OA\Property(property="message", type="string"),
	 *     		@OA\Property(property="field", type="string")
	 *     }
	 * )
	 *
	 * @OA\Schema (
	 *     schema="response_with_message_only",
	 *     type="object",
	 *     properties={
	 *       @OA\Property(property="message", type="string")
	 *       }
	 * )
	 *
	 */

	declare( strict_types=1 );

	namespace Gac\GoodFoodTracker;

	defined("BASE_PATH") or define("BASE_PATH", __DIR__);

	session_start();
	date_default_timezone_set("Europe/Belgrade");

	include_once "../vendor/autoload.php";

	use Dotenv\Dotenv;
	use Exception;
	use Gac\GoodFoodTracker\Core\Exceptions\AppNotInitializedException;
	use Gac\GoodFoodTracker\Core\Utility\Logger;
	use Gac\Routing\Exceptions\RouteNotFoundException;
	use Gac\Routing\Request;
	use Gac\Routing\Routes;
	use OpenApi\Generator;
	use ReflectionClass;

	$routes = new Routes();
	try {
		$dotenv = Dotenv::createImmutable(__DIR__ . "/../");
		$dotenv->load();

		$routes->add("/", function (Request $request) {
			$name = $request->get("name");
			$request->send([ "message" => "Hello " . ( $name ?? "World" ) ]);
		});

		$routes->add("/docs/{string:type}", function (string $type) {
			$openapi = Generator::scan([ __DIR__ ]);
			switch ( $type ) {
				case "yaml":
					header("Content-Type: text/plain");
					echo $openapi->toYaml();
					break;
				case "json":
				default:
					header("Content-Type: application/json");
					echo $openapi->toJson();
					break;

			}
		});

		require_once __DIR__ . "routes.php";

		$routes->handle();
	} catch ( RouteNotFoundException $ex ) {
		$routes->request
			->status(404)
			->send([
				"error" => [
					"message" => $ex->getMessage(),
					"field" => "",
				],
			]);
	} catch ( AppNotInitializedException $ex ) {
		$routes->request
			->status(500)
			->send([
				"error" => [
					"message" => "The app wasn't initialized properly {$ex->getMessage()}",
					"field" => "",
				],
			]);
	} catch ( Exception $ex ) {
		Logger::error($ex->getMessage());
		$routes->request->status((int) $ex->getCode() ?? 500)->send([
			"error" => [
				"class" => ( new ReflectionClass($ex) )->getShortName(),
				"message" => $ex->getMessage() ?? "Request failed",
				"field" => ( method_exists($ex, "getField") ) ? $ex->getField() : "",
			],
		]);
	}
