# HCOS LessonForge - Deployment Guide

This guide will walk you through deploying the application to a public Linux VPS (Virtual Private Server).

## Prerequisites
1.  **A Domain Name** (optional but recommended, e.g., `my-demo.com`).
2.  **A VPS Provider** Account (DigitalOcean, Linode, AWS Lightsail, or Hetzner).

## Step 1: Create a Server
1.  Create a standard **Ubuntu 22.04 LTS** Droplet/Instance.
    -   **Size**: The cheapest option (usually 1GB RAM / 1 CPU) is fine.
    -   **Region**: Closest to you (e.g., SFO, NYC).
    -   **Authentication**: Add your SSH Key or choose a password.
2.  Web Host will give you an **IP Address** (e.g., `192.168.1.100`).

## Step 2: Prepare the Server
1.  Open your terminal (PowerShell or Command Prompt).
2.  SSH into your new server:
    ```powershell
    C:\Windows\System32\OpenSSH\ssh.exe root@64.225.114.55
    ```

3.  **Password Change**:
    -   The first time you login, it will ask for the **current password** (check your email).
    -   Then it will ask for a **new password** (twice).
    -   *Tip: Use a strong password you can remember!*

4.  **Create a folder**:
    ```bash
    mkdir -p /var/www/lessonforge
    ```

## Step 3: Copy Files
You have two options to get your code to the server.

### Option A: Manual Copy (Recommended for Windows Users)
Open a **new** PowerShell window (keep the SSH one open separately) and run this **from your project folder**:

```powershell
# Copy all files to the server
C:\Windows\System32\OpenSSH\scp.exe -r * root@64.225.114.55:/var/www/lessonforge
```

### Option B: Git
1. Push your code to GitHub.
2. Clone it on the server:
   ```bash
   cd /var/www
   git clone https://github.com/your-username/your-repo.git lessonforge
   ```

## Step 4: Run Setup & Deploy
Back in your **Server Terminal** (SSH window):

1.  Go to the folder:
    ```bash
    cd /var/www/lessonforge
    ```
2.  Make scripts executable:
    ```bash
    chmod +x deployment/*.sh
    ```
3.  Run the setup script (installs Docker):
    ```bash
    ./deployment/vps-setup.sh
    ```
    *(Wait for it to finish...)*

4.  Start the application:
    ```bash
    ./deployment/deploy.sh
    ```

## Step 5: Verify
Open your browser and visit: `http://64.225.114.55`

You should see HCOS LessonForge running live!

---

## Troubleshooting
-   **Database is empty?**
    Run the seed command inside the production container:
    ```bash
    docker compose -f docker-compose.prod.yml exec -T db mariadb -u hcos -phcos_secret_2026 hcos_lessonforge < database/full_seed.sql
    ```
