<?php
/**
 * Autoload for IP Locator.
 *
 * @package Bootstrap
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

spl_autoload_register(
	function ( $class ) {
		$classname = $class;
		$filepath  = __DIR__ . '/';
		if ( strpos( $classname, 'IPLocator\\' ) === 0 ) {
			while ( strpos( $classname, '\\' ) !== false ) {
				$classname = substr( $classname, strpos( $classname, '\\' ) + 1, 1000 );
			}
			$filename = 'class-' . str_replace( '_', '-', strtolower( $classname ) ) . '.php';
			if ( strpos( $class, 'IPLocator\System\\' ) === 0 ) {
				$filepath = IPLOCATOR_INCLUDES_DIR . 'system/';
			} elseif ( strpos( $class, 'IPLocator\Plugin\Feature\\' ) === 0 ) {
				$filepath = IPLOCATOR_INCLUDES_DIR . 'features/';
			} elseif ( strpos( $class, 'IPLocator\Plugin\\' ) === 0 ) {
				$filepath = IPLOCATOR_INCLUDES_DIR . 'plugin/';
			} elseif ( strpos( $class, 'IPLocator\Library\\' ) === 0 ) {
				$filepath = IPLOCATOR_VENDOR_DIR;
			} elseif ( strpos( $class, 'IPLocator\API\\' ) === 0 ) {
				$filepath = IPLOCATOR_INCLUDES_DIR . 'api/';
			} elseif ( strpos( $class, 'IPLocator\\' ) === 0 ) {
				$filepath = IPLOCATOR_INCLUDES_DIR . 'api/';
			}
			if ( strpos( $filename, '-public' ) !== false ) {
				$filepath = IPLOCATOR_PUBLIC_DIR;
			}
			if ( strpos( $filename, '-admin' ) !== false ) {
				$filepath = IPLOCATOR_ADMIN_DIR;
			}
			$file = $filepath . $filename;
			if ( file_exists( $file ) ) {
				include_once $file;
			}
		}
	}
);
