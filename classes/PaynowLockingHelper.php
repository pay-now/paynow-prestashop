<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License (MIT)
 * that is bundled with this package in the file LICENSE.md.
 *
 * @author mElements S.A.
 * @copyright mElements S.A.
 * @license   MIT License
 */

class PaynowLockingHelper
{
    private const LOCKS_DIR = 'paynow-locks';
    private const LOCKS_PREFIX = 'paynow-lock-';
    private const LOCKED_TIME = 35;

    /**
     * @string
     */
    public $locksDirPath;

    /**
     * @var bool
     */
    public $lockEnabled = true;

    /**
     * Constructor of PaynowLockingHelper
     */
    public function __construct() {

        // Setup locks dir
        try {
            $lockPath = dirname(__FILE__) . '/..' . DIRECTORY_SEPARATOR . self::LOCKS_DIR;
            @mkdir( $lockPath );

            if ( is_dir( $lockPath ) && is_writable( $lockPath ) ) {
                $this->locksDirPath = $lockPath;
            } else {
                $this->locksDirPath = sys_get_temp_dir();
            }
        } catch ( \Exception $exception ) {
            PaynowLogger::error(
                'Error occurred when creating locking dir.',
                [
                    'exception' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine()
                ]
            );
            $this->locksDirPath = sys_get_temp_dir();
        }

        if ( ! is_writable( $this->locksDirPath ) ) {
            PaynowLogger::error( 'Locking mechanism disabled, no locks path available.' );
        }

        $this->lockEnabled = is_writable( $this->locksDirPath );
    }

    /**
     * @param $externalId
     * @return bool
     */
    public function checkAndCreate( $externalId ) {

        if ( ! $this->lockEnabled ) {
            return false;
        }
        $lockFilePath = $this->generateLockPath( $externalId );
        $lockExists = file_exists( $lockFilePath );

        if ( $lockExists && ( filemtime( $lockFilePath ) + self::LOCKED_TIME ) > time() ) {
            return true;
        } else {
            $this->create( $externalId, $lockExists );

            return false;
        }
    }

    /**
     * @param $externalId
     * @return void
     */
    public function delete( $externalId ) {

        if ( empty( $externalId ) ) {
            return;
        }

        $lockFilePath = $this->generateLockPath( $externalId );

        if ( file_exists( $lockFilePath ) ) {
            unlink( $lockFilePath );
        }
    }

    /**
     * @param $externalId
     * @param $lockExists
     * @return void
     */
    private function create( $externalId, $lockExists ) {

        $lockPath = $this->generateLockPath( $externalId );
        if ( $lockExists ) {
            touch( $lockPath );
        } else {
            $fileSaved = @file_put_contents($lockPath, '');

            if ( false === $fileSaved ) {
                PaynowLogger::error(
                    'Locking mechanism disabled, no locks path available.',
                    [
                        'external_id' => $externalId,
                        'lock_path'   => $lockPath,
                    ]
                );
            }
        }
    }

    /**
     * @param $externalId
     * @return string
     */
    private function generateLockPath( $externalId ) {

        return $this->locksDirPath . DIRECTORY_SEPARATOR . self::LOCKS_PREFIX . md5( $externalId ) . '.lock';
    }
}
