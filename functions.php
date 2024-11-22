<?php
/**
 * Global functions.
 *
 * @package Functions
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   3.0.0
 */

if ( ! function_exists('decalog_get_psr_log_version') ) {
	/**
	 * Get the needed version of PSR-3.
	 *
	 * @return  int  The PSR-3 needed version.
	 * @since 4.0.0
	 */
	function decalog_get_psr_log_version() {
		$required = 1;
		if ( ! defined( 'DECALOG_PSR_LOG_VERSION') ) {
			define( 'DECALOG_PSR_LOG_VERSION', 'V1' );
		}
		switch ( strtolower( DECALOG_PSR_LOG_VERSION ) ) {
			case 'v3':
				$required = 3;
				break;
			case 'auto':
				if ( class_exists( '\Psr\Log\NullLogger') ) {
					$reflection = new \ReflectionMethod(\Psr\Log\NullLogger::class, 'log');
					foreach ( $reflection->getParameters() as $param ) {
						if ( 'message' === $param->getName() ) {
							if ( str_contains($param->getType() ?? '', '|') ) {
								$required = 3;
							}
						}
					}
				}
		}
		return $required;
	}
}

/**
 * Downloads a URL to a local temporary file using the WordPress HTTP API.
 *
 * Please note that the calling function must unlink() the file.
 *
 * @param string $url                    The URL of the file to download.
 * @param int    $timeout                The timeout for the request to download the file.
 *                                       Default 300 seconds.
 * @param bool   $signature_verification Whether to perform Signature Verification.
 *                                       Default false.
 * @param string $ua                     The user-agent to use.
 * @return string|WP_Error Filename on success, WP_Error on failure.
 * @since   3.0.0
 */
function iplocator_download_url( $url, $timeout = 300, $signature_verification = false, $ua = '' ) {
	// WARNING: The file is not automatically deleted, the script must unlink() the file.
	if ( ! $url ) {
		return new WP_Error( 'http_no_url', __( 'Invalid URL Provided.' ) );
	}

	$url_filename = basename( parse_url( $url, PHP_URL_PATH ) );

	$tmpfname = wp_tempnam( $url_filename );
	if ( ! $tmpfname ) {
		return new WP_Error( 'http_no_file', __( 'Could not create Temporary file.' ) );
	}

	$http     = _wp_http_get_object();
	$response = $http->get(
		$url,
		[
			'timeout'    => $timeout,
			'stream'     => true,
			'filename'   => $tmpfname,
			'headers'    => [
				'user-agent' => $ua,
			]
		]
	);

	if ( is_wp_error( $response ) ) {
		unlink( $tmpfname );
		return $response;
	}

	$response_code = wp_remote_retrieve_response_code( $response );

	if ( 200 != $response_code ) {
		$data = array(
			'code' => $response_code,
		);

		// Retrieve a sample of the response body for debugging purposes.
		$tmpf = fopen( $tmpfname, 'rb' );
		if ( $tmpf ) {
			/**
			 * Filters the maximum error response body size in `download_url()`.
			 *
			 * @since 5.1.0
			 *
			 * @see download_url()
			 *
			 * @param int $size The maximum error response body size. Default 1 KB.
			 */
			$response_size = apply_filters( 'download_url_error_max_body_size', KB_IN_BYTES );
			$data['body']  = fread( $tmpf, $response_size );
			fclose( $tmpf );
		}

		unlink( $tmpfname );
		return new WP_Error( 'http_404', trim( wp_remote_retrieve_response_message( $response ) ), $data );
	}

	$content_md5 = wp_remote_retrieve_header( $response, 'content-md5' );
	if ( $content_md5 ) {
		$md5_check = verify_file_md5( $tmpfname, $content_md5 );
		if ( is_wp_error( $md5_check ) ) {
			unlink( $tmpfname );
			return $md5_check;
		}
	}

	// If the caller expects signature verification to occur, check to see if this URL supports it.
	if ( $signature_verification ) {
		/**
		 * Filters the list of hosts which should have Signature Verification attempted on.
		 *
		 * @since 5.2.0
		 *
		 * @param string[] $hostnames List of hostnames.
		 */
		$signed_hostnames       = apply_filters( 'wp_signature_hosts', array( 'wordpress.org', 'downloads.wordpress.org', 's.w.org' ) );
		$signature_verification = in_array( parse_url( $url, PHP_URL_HOST ), $signed_hostnames, true );
	}

	// Perform signature valiation if supported.
	if ( $signature_verification ) {
		$signature = wp_remote_retrieve_header( $response, 'x-content-signature' );
		if ( ! $signature ) {
			// Retrieve signatures from a file if the header wasn't included.
			// WordPress.org stores signatures at $package_url.sig.

			$signature_url = false;
			$url_path      = parse_url( $url, PHP_URL_PATH );

			if ( '.zip' === substr( $url_path, -4 ) || '.tar.gz' === substr( $url_path, -7 ) ) {
				$signature_url = str_replace( $url_path, $url_path . '.sig', $url );
			}

			/**
			 * Filters the URL where the signature for a file is located.
			 *
			 * @since 5.2.0
			 *
			 * @param false|string $signature_url The URL where signatures can be found for a file, or false if none are known.
			 * @param string $url                 The URL being verified.
			 */
			$signature_url = apply_filters( 'wp_signature_url', $signature_url, $url );

			if ( $signature_url ) {
				$signature_request = wp_safe_remote_get(
					$signature_url,
					array(
						'limit_response_size' => 10 * KB_IN_BYTES, // 10KB should be large enough for quite a few signatures.
					)
				);

				if ( ! is_wp_error( $signature_request ) && 200 === wp_remote_retrieve_response_code( $signature_request ) ) {
					$signature = explode( "\n", wp_remote_retrieve_body( $signature_request ) );
				}
			}
		}

		// Perform the checks.
		$signature_verification = verify_file_signature( $tmpfname, $signature, basename( parse_url( $url, PHP_URL_PATH ) ) );
	}

	if ( is_wp_error( $signature_verification ) ) {
		if (
			/**
			 * Filters whether Signature Verification failures should be allowed to soft fail.
			 *
			 * WARNING: This may be removed from a future release.
			 *
			 * @since 5.2.0
			 *
			 * @param bool   $signature_softfail If a softfail is allowed.
			 * @param string $url                The url being accessed.
			 */
		apply_filters( 'wp_signature_softfail', true, $url )
		) {
			$signature_verification->add_data( $tmpfname, 'softfail-filename' );
		} else {
			// Hard-fail.
			unlink( $tmpfname );
		}

		return $signature_verification;
	}

	return $tmpfname;
}
