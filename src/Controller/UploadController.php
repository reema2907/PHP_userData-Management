<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mailer\MailerInterface;
use App\Message\Notification;

class UploadController extends AbstractController
{
    #[Route('/api/upload', name: 'api_upload', methods: ['POST'])]
    public function upload(Request $request, EntityManagerInterface $em, MailerInterface $mailer, MessageBusInterface $bus): Response
    {

        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], 400);
        }

        // 3. Parse CSV
        $handle = fopen($file->getPathname(), 'r');
        $header = fgetcsv($handle);
        $users = [];
        $rowNumber = 1;
        while (($data = fgetcsv($handle)) !== false) {
            // Log the raw CSV row
            error_log('Parsing row ' . $rowNumber . ': ' . json_encode($data));
            $user = new User();
            $user->setName($data[0]);
            $user->setEmail($data[1]);
            $user->setUsername($data[2]);
            $user->setAddress($data[3]);
            $user->setRole($data[4]);
            $em->persist($user);
            $users[] = $user;
            $rowNumber++;
        }
        fclose($handle);
        $em->flush();

        foreach ($users as $user) {
            $bus->dispatch(new Notification($user->getEmail(), $user->getName()));
        }

        return $this->json(['status' => 'success', 'users_added' => count($users)]);
    }

    #[Route('/api/users', name: 'api_users', methods: ['GET'])]
    public function listUsers(EntityManagerInterface $em): Response
    {
        $users = $em->getRepository(User::class)->findAll();
        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'address' => $user->getAddress(),
                'role' => $user->getRole(),
            ];
        }
        return $this->json($data);
    }

    #[Route('/api/backup', name: 'api_backup', methods: ['GET'])]
    public function backupDatabase(): Response
    {
        // Only allow admins (uncomment if you have security enabled)
        // $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Database credentials (ideally, get these from env or config)
        $dbHost = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $dbUser = $_ENV['DB_USER'] ?? 'root';
        $dbPass = $_ENV['DB_PASSWORD'] ?? '';
        $dbName = $_ENV['DB_NAME'] ?? 'user_management_api';

        $backupFile = sys_get_temp_dir() . '/backup_' . date('Ymd_His') . '.sql';

        // Build the mysqldump command
        $command = sprintf(
            'mysqldump -h%s -u%s %s %s > %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            $dbPass ? '-p' . escapeshellarg($dbPass) : '',
            escapeshellarg($dbName),
            escapeshellarg($backupFile)
        );

        // Execute the command
        system($command, $resultCode);

        if ($resultCode !== 0 || !file_exists($backupFile)) {
            return $this->json(['error' => 'Backup failed'], 500);
        }

        // Return the file as a download
        return $this->file($backupFile, 'backup.sql')->deleteFileAfterSend(true);
    }

    #[Route('/api/restore', name: 'api_restore', methods: ['POST'])]
    public function restoreDatabase(Request $request): Response
    {
        // Only allow admins (uncomment if you have security enabled)
        // $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], 400);
        }

        // Save the uploaded file to a temp location
        $backupFile = sys_get_temp_dir() . '/restore_' . uniqid() . '.sql';
        $file->move(dirname($backupFile), basename($backupFile));

        // Database credentials (ideally, get these from env or config)
        $dbHost = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $dbUser = $_ENV['DB_USER'] ?? 'root';
        $dbPass = $_ENV['DB_PASSWORD'] ?? '';
        $dbName = $_ENV['DB_NAME'] ?? 'user_management_api';

        // Build the mysql command
        $command = sprintf(
            'mysql -h%s -u%s %s %s < %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            $dbPass ? '-p' . escapeshellarg($dbPass) : '',
            escapeshellarg($dbName),
            escapeshellarg($backupFile)
        );

        // Execute the command
        system($command, $resultCode);

        // Remove the temp file
        @unlink($backupFile);

        if ($resultCode !== 0) {
            return $this->json(['error' => 'Restore failed'], 500);
        }

        return $this->json(['status' => 'success', 'message' => 'Database restored successfully']);
    }
}
