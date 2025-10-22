# 🚀 Production Deployment Guide

## Quick Deploy (All Steps in One Command)

### SSH into your production server first:
```bash
ssh monster@diyetlenio.com
cd /home/monster/diyetlenio
```

### Then run ONE of these commands:

#### Option 1: Using MySQL CLI (Recommended)
```bash
mysql -u root -p diyetlenio_db < scripts/complete-setup.sql
```

#### Option 2: Using sudo
```bash
sudo mysql diyetlenio_db < scripts/complete-setup.sql
```

#### Option 3: Web-based (via phpMyAdmin)
1. Login to phpMyAdmin
2. Select `diyetlenio_db` database
3. Go to SQL tab
4. Copy and paste contents of `scripts/complete-setup.sql`
5. Click "Go"

---

## What This Will Do:

✅ Create `client_profiles` table (for client personal information)
✅ Create `weight_tracking` table (for weight measurements)
✅ Capitalize all user names (Mehmet Yılmaz, not mehmet yılmaz)
✅ Verify everything was created successfully

---

## Verification

After running the setup, verify with:

```bash
mysql -u root -p diyetlenio_db
```

Then run:
```sql
-- Check tables exist
SHOW TABLES LIKE 'client_profiles';
SHOW TABLES LIKE 'weight_tracking';

-- Check table structures
DESCRIBE client_profiles;
DESCRIBE weight_tracking;

-- Check some user names (should be capitalized)
SELECT full_name FROM users LIMIT 10;
```

---

## Test the Features

1. **Client Profile Page:**
   - Login as a client
   - Go to: https://www.diyetlenio.com/client/profile.php
   - You should see profile form with personal info fields

2. **Weight Tracking Page:**
   - Login as a client
   - Go to: https://www.diyetlenio.com/client/weight-tracking.php
   - You should see weight tracking interface

---

## Troubleshooting

### "Table already exists" error
This is normal and safe - it means the table was already created.

### "Access denied" error
Try with sudo:
```bash
sudo mysql diyetlenio_db < scripts/complete-setup.sql
```

### Want to see changes before applying?
Add `--verbose` to see what's happening:
```bash
mysql -u root -p diyetlenio_db --verbose < scripts/complete-setup.sql
```

---

## Safety Notes

✅ Safe to run multiple times (uses IF NOT EXISTS)
✅ Only updates names that need fixing
✅ No data will be deleted
✅ No existing tables will be modified (only adds new ones)

---

## Need Help?

If you encounter any errors:
1. Copy the error message
2. Check `/var/log/mysql/error.log`
3. Verify database name with: `grep DB_DATABASE .env`

---

**Ready to deploy? Run this single command:**

```bash
mysql -u root -p diyetlenio_db < scripts/complete-setup.sql
```

**Enter your MySQL root password when prompted, and you're done!** 🎉
