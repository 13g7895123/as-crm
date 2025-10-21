# Cron Job Setup Guide

This document explains how to set up scheduled tasks for the CRM RBAC system.

## Available Commands

### 1. Clean Expired Roles
Removes expired role assignments from the database.

```bash
php spark roles:clean-expired
```

**Options:**
- `--dry-run` - Show what would be deleted without actually deleting
- `--verbose` - Show detailed information about expired roles

**Recommended Schedule:** Every 5 minutes

### 2. Notify Expiring Roles
Sends notifications for roles expiring within 7, 3, and 1 day(s).

```bash
php spark roles:notify-expiring
```

**Options:**
- `--days=7,3,1` - Specify custom notification thresholds
- `--dry-run` - Show what notifications would be sent without sending
- `--verbose` - Show detailed information about expiring roles

**Recommended Schedule:** Once daily (e.g., at 9:00 AM)

---

## Cron Configuration

### Linux/Unix Crontab

Edit your crontab:
```bash
crontab -e
```

Add the following lines:

```cron
# Clean expired roles every 5 minutes
*/5 * * * * cd /path/to/crm/backend && php spark roles:clean-expired >> /var/log/crm/clean-expired.log 2>&1

# Send expiring role notifications daily at 9:00 AM
0 9 * * * cd /path/to/crm/backend && php spark roles:notify-expiring >> /var/log/crm/notify-expiring.log 2>&1
```

**Note:** Replace `/path/to/crm/backend` with your actual backend directory path.

### Docker Environment

If running in Docker, use `docker compose exec`:

```cron
# Clean expired roles every 5 minutes
*/5 * * * * docker compose -f /path/to/crm/docker-compose.yml exec -T backend php spark roles:clean-expired >> /var/log/crm/clean-expired.log 2>&1

# Send expiring role notifications daily at 9:00 AM
0 9 * * * docker compose -f /path/to/crm/docker-compose.yml exec -T backend php spark roles:notify-expiring >> /var/log/crm/notify-expiring.log 2>&1
```

### Using Dockerfile

Add a cron service to your `docker-compose.yml`:

```yaml
services:
  # ... existing services ...

  cron:
    image: your-backend-image
    container_name: crm-cron
    depends_on:
      - database
    volumes:
      - ./backend:/var/www/html
    command: >
      sh -c "
        echo '*/5 * * * * cd /var/www/html && php spark roles:clean-expired >> /var/log/clean-expired.log 2>&1' >> /etc/crontabs/root &&
        echo '0 9 * * * cd /var/www/html && php spark roles:notify-expiring >> /var/log/notify-expiring.log 2>&1' >> /etc/crontabs/root &&
        crond -f -l 2
      "
```

---

## Testing Commands

### Test Clean Expired Roles (Dry Run)

```bash
php spark roles:clean-expired --dry-run --verbose
```

Expected output:
```
開始清理過期的角色指派...

找到 3 筆過期的角色指派。

過期的角色指派列表:
┌────┬────────┬──────────┬─────────────────────┐
│ ID │ 使用者 │ 角色     │ 過期時間            │
├────┼────────┼──────────┼─────────────────────┤
│ 12 │ user1  │ 業務主管 │ 2024-12-31 23:59:59 │
│ 15 │ user2  │ 區域經理 │ 2025-01-15 10:00:00 │
│ 18 │ user3  │ 客服專員 │ 2025-02-01 00:00:00 │
└────┴────────┴──────────┴─────────────────────┘

[模擬模式] 不會實際刪除記錄。
將刪除 3 筆記錄。

清理完成！
```

### Test Notify Expiring Roles (Dry Run)

```bash
php spark roles:notify-expiring --dry-run --verbose
```

Expected output:
```
開始檢查即將過期的角色指派...

檢查 7 天內即將過期的角色...
  找到 2 筆即將過期的角色。

  角色列表 (即將於 7 天後過期):
┌────┬────────┬─────────────────┬──────────┬─────────────────────┐
│ ID │ 使用者 │ 信箱            │ 角色     │ 過期時間            │
├────┼────────┼─────────────────┼──────────┼─────────────────────┤
│ 20 │ user4  │ user4@email.com │ 業務主管 │ 2025-10-28 23:59:59 │
│ 22 │ user5  │ user5@email.com │ 區域經理 │ 2025-10-29 10:00:00 │
└────┴────────┴─────────────────┴──────────┴─────────────────────┘

  [模擬模式] 將發送 2 則通知。

檢查 3 天內即將過期的角色...
  沒有找到 3 天內過期的角色。

檢查 1 天內即將過期的角色...
  找到 1 筆即將過期的角色。
  [模擬模式] 將發送 1 則通知。

通知檢查完成！
總共處理 3 則通知。
```

---

## Log Files

Create log directory:
```bash
mkdir -p /var/log/crm
chmod 755 /var/log/crm
```

Log files:
- `/var/log/crm/clean-expired.log` - Clean expired roles command output
- `/var/log/crm/notify-expiring.log` - Notify expiring roles command output
- `backend/writable/logs/log-*.log` - CodeIgniter application logs

---

## Monitoring

### Check Last Run Time

```bash
# Linux/Unix
grep "roles:clean-expired" /var/log/crm/clean-expired.log | tail -1
grep "roles:notify-expiring" /var/log/crm/notify-expiring.log | tail -1
```

### Check Application Logs

```bash
# View recent cleanup events
grep "Cleaned.*expired role" backend/writable/logs/log-*.log

# View recent notifications
grep "expiring role notification" backend/writable/logs/log-*.log
```

---

## Troubleshooting

### Command Not Found

Ensure you're in the correct directory:
```bash
cd /path/to/crm/backend
php spark list
```

### Permission Denied

Grant execute permissions:
```bash
chmod +x backend/spark
```

### Database Connection Failed

Check database configuration:
```bash
php spark db:table migrations
```

### Email Notifications Not Sending

1. Check email configuration in `backend/app/Config/Email.php`
2. Enable debug mode in NotifyExpiringRolesCommand.php
3. Check application logs for errors

---

## Manual Execution

### Run Cleanup Manually

```bash
cd backend
php spark roles:clean-expired --verbose
```

### Run Notifications Manually

```bash
cd backend
php spark roles:notify-expiring --verbose
```

### Custom Notification Thresholds

```bash
php spark roles:notify-expiring --days=14,7,3,1
```

---

## Best Practices

1. **Always test with --dry-run first** before running commands in production
2. **Monitor log files regularly** to ensure commands are running successfully
3. **Set up log rotation** to prevent log files from growing too large
4. **Configure email settings** before enabling notifications
5. **Test notifications** with a test user account first
6. **Review cleanup results** periodically to ensure data integrity

---

## Next Steps

1. Set up cron jobs according to your environment
2. Configure email settings for notifications
3. Test commands with --dry-run
4. Monitor logs for the first few days
5. Adjust schedules as needed based on your requirements
