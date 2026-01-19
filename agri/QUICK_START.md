# 🚀 Quick Start - Database Setup (5 minutes)

## Step 1: Create Database (Choose One Option)

### Option A: phpMyAdmin (EASIEST - Recommended)
1. Go to `http://localhost/phpmyadmin`
2. Click **"New"** on the left sidebar
3. Enter database name: `agri_system`
4. Click **"Create"**
5. Click **"Import"** tab
6. Select file: `c:\wamp64\www\agri\config\schema.sql`
7. Click **"Import"** button
8. Wait for success message ✅

### Option B: Command Line (FASTEST)
```bash
# Open Command Prompt or PowerShell
# Navigate to: c:\wamp64\www\agri
cd c:\wamp64\www\agri

# Run:
mysql -u root -p < config/schema.sql

# Enter your MySQL password (usually blank for WAMP)
```

### Option C: PowerShell Script (AUTOMATIC)
```powershell
# Open PowerShell as Administrator
cd c:\wamp64\www\agri
powershell -ExecutionPolicy Bypass -File setup-db.ps1
# Follow prompts
```

---

## Step 2: Add Sample Data

### Option A: phpMyAdmin
1. Go to `http://localhost/phpmyadmin`
2. Select database: `agri_system`
3. Click **"SQL"** tab
4. Copy ALL content from: `MANUAL_DATABASE_SETUP.sql`
5. Paste into the SQL editor
6. Click **"Go"** button
7. Wait for success ✅

### Option B: Command Line
```bash
mysql -u root -p agri_system < MANUAL_DATABASE_SETUP.sql
```

---

## Step 3: Verify Setup

In phpMyAdmin SQL tab, run:
```sql
SELECT * FROM users;
SELECT * FROM products;
SELECT * FROM roles;
```

You should see:
- ✅ 3 users (farmer, consumer, admin)
- ✅ 5 products
- ✅ 3 roles

---

## Step 4: Test Login

Go to: `http://localhost/agri/auth/login.php`

Use these credentials:

| Role | Email | Password |
|------|-------|----------|
| Farmer | farmer@test.com | password123 |
| Consumer | consumer@test.com | password123 |
| Admin | admin@test.com | password123 |

---

## Step 5: Test the System

### As Consumer:
1. Login with `consumer@test.com / password123`
2. Go to **"Browse Products"**
3. Add items to cart
4. Go to **"View Cart"**
5. Click **"Checkout"**
6. Select fulfillment type and submit
7. See order confirmation ✅

### As Farmer:
1. Login with `farmer@test.com / password123`
2. Go to **"Farmer Orders"** or **"Dashboard"**
3. See orders with statistics
4. Click on order to view timeline
5. Update order status ✅

---

## 📋 Database Files Provided

| File | Purpose |
|------|---------|
| `config/schema.sql` | Database schema (tables, structure) |
| `MANUAL_DATABASE_SETUP.sql` | Sample data to copy-paste |
| `DATABASE_SETUP.md` | Detailed setup guide |
| `setup-db.ps1` | PowerShell automation script |
| `setup-db.sh` | Linux/Mac bash script |

---

## ⚠️ Troubleshooting

### "MySQL: Access denied"
- Check your MySQL password in `config/database.php`
- Default for WAMP is usually blank or `root`

### "Can't connect to localhost"
- Make sure WAMP/LAMP is running
- Check MySQL service in Windows Services

### "Database exists" error
- The database already exists - this is fine!
- Skip the schema.sql import
- Just run the sample data insert

### "Foreign key constraint fails"
- Make sure tables exist first
- Run schema.sql before sample data
- Check tables were created: `SHOW TABLES;`

---

## ✅ Verification Checklist

- [ ] Created database `agri_system`
- [ ] Imported `config/schema.sql`
- [ ] Added sample data (3 users, 5 products)
- [ ] Can login with farmer@test.com
- [ ] Can login with consumer@test.com
- [ ] Can see products in browse page
- [ ] Can add to cart
- [ ] Can checkout
- [ ] Can view orders as farmer

---

## 🎯 You're Done!

Once you see the order management system working:
- ✅ Frontend integration complete
- ✅ Order creation working
- ✅ Status tracking working
- ✅ Filters working
- ✅ Timeline showing

**Congratulations! The system is ready for testing! 🎉**

---

## 📞 Need Help?

1. Check **DATABASE_SETUP.md** for detailed instructions
2. Review **FRONTEND_INTEGRATION_SUMMARY.md** for feature overview
3. Check error logs in browser console (F12)
4. Check MySQL error log

---

**Estimated Time: 5-10 minutes** ⏱️

**Next: Visit `http://localhost/agri/auth/login.php` to get started!**
