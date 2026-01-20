<?php
require_once 'vendor/autoload.php';

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;

/**
 * Authenticate and return Google Drive service
 */
function getDriveService() {
    $client = new Client();
    $client->setAuthConfig('C:/xampp/htdocs/luntianJMS/credentials/lbs-job-submission-42dd19ad9718.json');
    $client->addScope(Drive::DRIVE);
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    return new Drive($client);
}

/**
 * Create a Google Drive folder
 */
function createDriveFolder($service, $folderName, $parentFolderId = null) {
    $fileMetadata = new DriveFile([
        'name' => $folderName,
        'mimeType' => 'application/vnd.google-apps.folder'
    ]);

    // ✅ Ensure parents is always an array
    if (!empty($parentFolderId)) {
        if (is_string($parentFolderId)) {
            $fileMetadata->setParents([$parentFolderId]);
        } elseif (is_array($parentFolderId)) {
            $fileMetadata->setParents($parentFolderId);
        }
    }

    // ✅ Ensure second parameter is always an array, not a string
    $params = [
        'fields' => 'id'
    ];

    // 🧪 Safety check — just in case something weird happens
    if (!is_array($params)) {
        $params = ['fields' => 'id'];
    }

    // ✅ Create the folder
    $folder = $service->files->create($fileMetadata, $params);

    return $folder->id;
}
?>
