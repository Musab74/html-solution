# 🔥 MongoDB Real-Time User Sync Architecture
## HRDe LMS - User Capacity & Name Collision Prevention

---

## 📊 **CURRENT SYSTEM ANALYSIS**

### **1. User Capacity (MySQL - Legacy System)**

#### **Database Connection Limits:**
```sql
Server: 222.239.103.77
Database: HRDLMS
Connection Pool: Based on MySQL max_connections setting
Current Estimate: ~1,000-2,000 concurrent users (typical MySQL config)
```

#### **Theoretical Maximum Users:**
- **MySQL Default:** 151 concurrent connections
- **Recommended Production:** 500-1,000 connections
- **Enterprise Setup:** 2,000-5,000+ connections
- **Your Server:** Check with: `SHOW VARIABLES LIKE 'max_connections';`

#### **Active Session Tracking:**
```php
// Current implementation in login_ok.php
Table: LoginIng
Fields:
- idx (unique identifier)
- ID (user ID - unique per session)
- SessionID (PHP session ID)
- IP (user IP address)
- RegDate (login timestamp)

// Duplicate login prevention
DELETE FROM LoginIng WHERE ID='$ID';  // Remove old sessions
INSERT INTO LoginIng(...);            // Add new session
```

**✅ Current Capacity:** System tracks ALL active users in `LoginIng` table
**🎯 Concurrent Users:** Limited by MySQL max_connections (typically 500-2,000)

---

## 👥 **USER ROLE SYSTEM**

### **Member Types (from login system):**

```javascript
USER ROLES IDENTIFIED:

1. MemberType (Training Category):
   - "A" → 사업주훈련회원 (Corporate Training Member)
   - "B" → 근로자 내일배움카드회원 (Worker Tomorrow Learning Card Member)

2. EduManager (Education Manager):
   - "Y" → Education Administrator
   - "N" → Regular Student

3. TestID (Test Account):
   - "Y" → Test/Demo Account
   - "N" → Real User

4. Admin Roles (from hrd_manager):
   - Administrator (관리자)
   - Sales Manager (영업자)
   - Instructor (첨삭강사)
   - Department Hierarchy System

5. User Status:
   - Sleep = "Y/N" (Dormant Account)
   - MemberOut = "Y/N" (Withdrawn)
   - UseYN = "Y/N" (Active/Inactive)
   - Mandatory = "Y/N" (Terms Agreement)
   - PassChange = "Y/N" (Password Change Required)
   - AbilityYN = "Y/N" (Competency Assessment Completed)
```

---

## 🗂️ **MEMBER TABLE STRUCTURE (MySQL)**

```sql
-- Critical fields for MongoDB sync
SELECT 
    -- UNIQUE IDENTIFIERS
    ID,              -- ✅ PRIMARY KEY (username - unique)
    idx,             -- Auto-increment ID
    Email,           -- Email address
    
    -- PERSONAL INFO
    Name,            -- ⚠️ NOT UNIQUE (can have duplicates!)
    Mobile,          -- Phone (encrypted)
    Gender,          -- M/F
    
    -- ROLE & STATUS
    MemberType,      -- A/B (training type)
    EduManager,      -- Y/N (is admin)
    TestID,          -- Y/N (is test account)
    Sleep,           -- Y/N (dormant)
    MemberOut,       -- Y/N (withdrawn)
    UseYN,           -- Y/N (active)
    
    -- SECURITY
    Pwd,             -- Password (SHA256 encrypted)
    SessionID,       -- Current session ID
    LastLogin,       -- Last login timestamp
    LastLoginIP,     -- Last login IP
    
    -- METADATA
    RegDate,         -- Registration date
    CertPassNumber   -- Certification number
    
FROM Member;
```

---

## 🚨 **NAME COLLISION PROBLEM & SOLUTION**

### **⚠️ Problem: Multiple Users with Same Name**

```sql
-- Example scenario:
ID: "kim123"    | Name: "김철수" | Role: Student
ID: "kim456"    | Name: "김철수" | Role: Admin
ID: "kim789"    | Name: "김철수" | Role: Manager

-- All three have the same name but different IDs!
```

### **✅ Solution: Composite Unique Key Strategy**

```typescript
// MongoDB Schema for User Sync
interface MongoDBUser {
  // PRIMARY IDENTIFIERS (Prevent Duplicates)
  _id: ObjectId;                    // MongoDB auto ID
  userId: string;                   // MySQL ID (UNIQUE INDEX)
  userIdx: number;                  // MySQL idx (UNIQUE INDEX)
  email: string;                    // Email (UNIQUE INDEX)
  
  // PERSONAL INFO
  name: string;                     // Name (NOT unique - can duplicate!)
  displayName: string;              // "Name (ID)" for UI display
  mobile: string;                   // Encrypted phone
  gender: "M" | "F";
  
  // ROLE SYSTEM
  memberType: "A" | "B";            // Training type
  isEduManager: boolean;            // Admin flag
  isTestAccount: boolean;           // Test account flag
  
  // ENHANCED ROLES
  roles: string[];                  // ["student", "admin", "manager"]
  permissions: string[];            // Granular permissions
  departmentId?: string;            // Organization unit
  
  // STATUS FLAGS
  isActive: boolean;                // UseYN
  isSleep: boolean;                 // Sleep status
  isWithdrawn: boolean;             // MemberOut status
  
  // SESSION TRACKING
  currentSession: {
    sessionId: string;              // PHP session ID
    ipAddress: string;              // Current IP
    loginTime: Date;                // Login timestamp
    deviceType: "web" | "mobile";  // Device
    isOnline: boolean;              // Real-time status
  };
  
  // LIVEKIT SESSION (for video streaming)
  liveKitSession?: {
    participantId: string;          // LiveKit participant ID
    roomName: string;               // Current room
    joinedAt: Date;
    metadata: object;
  };
  
  // AUDIT TRAIL
  lastSync: Date;                   // Last MongoDB sync
  createdAt: Date;
  updatedAt: Date;
  
  // METADATA
  certPassNumber?: string;
  abilityTestCompleted: boolean;
  passwordChangeRequired: boolean;
  termsAgreed: boolean;
}

// INDEXES (Critical for performance and uniqueness)
db.users.createIndex({ userId: 1 }, { unique: true });
db.users.createIndex({ userIdx: 1 }, { unique: true });
db.users.createIndex({ email: 1 }, { unique: true });
db.users.createIndex({ "currentSession.sessionId": 1 });
db.users.createIndex({ "currentSession.isOnline": 1 });
db.users.createIndex({ name: 1 });  // NOT unique - allows duplicates
db.users.createIndex({ roles: 1 });
db.users.createIndex({ isActive: 1, isWithdrawn: 1 });
```

---

## 🔄 **REAL-TIME SYNC STRATEGY**

### **Option 1: Event-Driven Sync (Recommended)**

```typescript
// Node.js Sync Service
import { createClient } from 'redis';
import { MongoClient } from 'mongodb';
import mysql from 'mysql2/promise';

// Redis PubSub for real-time events
const redisClient = createClient();
const redisPub = redisClient.duplicate();

// Channels
const USER_LOGIN_CHANNEL = 'user:login';
const USER_LOGOUT_CHANNEL = 'user:logout';
const USER_UPDATE_CHANNEL = 'user:update';

// Sync user to MongoDB on login
async function syncUserOnLogin(userId: string) {
  // 1. Fetch from MySQL
  const [user] = await mysqlPool.query(
    `SELECT 
      ID, idx, Name, Email, Mobile, Gender,
      MemberType, EduManager, TestID,
      Sleep, MemberOut, UseYN,
      LastLogin, LastLoginIP,
      AbilityYN, Mandatory, PassChange
    FROM Member 
    WHERE ID = ? AND UseYN = 'Y'`,
    [userId]
  );
  
  if (!user) throw new Error('User not found');
  
  // 2. Transform to MongoDB format
  const mongoUser = {
    userId: user.ID,
    userIdx: user.idx,
    email: user.Email,
    name: user.Name,
    displayName: `${user.Name} (${user.ID})`, // ✅ Prevents UI confusion
    mobile: user.Mobile,
    gender: user.Gender,
    
    // Roles
    memberType: user.MemberType,
    isEduManager: user.EduManager === 'Y',
    isTestAccount: user.TestID === 'Y',
    roles: getRolesArray(user),
    
    // Status
    isActive: user.UseYN === 'Y',
    isSleep: user.Sleep === 'Y',
    isWithdrawn: user.MemberOut === 'Y',
    
    // Session
    currentSession: {
      sessionId: await getSessionId(user.ID),
      ipAddress: user.LastLoginIP,
      loginTime: new Date(user.LastLogin),
      deviceType: detectDevice(),
      isOnline: true
    },
    
    // Metadata
    abilityTestCompleted: user.AbilityYN === 'Y',
    passwordChangeRequired: user.PassChange === 'N',
    termsAgreed: user.Mandatory === 'Y',
    
    lastSync: new Date(),
    updatedAt: new Date()
  };
  
  // 3. Upsert to MongoDB (prevents duplicates)
  await mongoDb.collection('users').updateOne(
    { userId: user.ID },        // Find by unique ID
    { $set: mongoUser },        // Update data
    { upsert: true }            // Insert if not exists
  );
  
  // 4. Publish real-time event
  await redisPub.publish(USER_LOGIN_CHANNEL, JSON.stringify({
    userId: user.ID,
    name: user.Name,
    displayName: mongoUser.displayName,
    roles: mongoUser.roles,
    timestamp: new Date()
  }));
  
  return mongoUser;
}

// Helper: Convert user data to role array
function getRolesArray(user: any): string[] {
  const roles: string[] = ['student']; // Default role
  
  if (user.EduManager === 'Y') roles.push('admin', 'manager');
  if (user.TestID === 'Y') roles.push('test-user');
  
  // Check admin level from Staff tables
  // (Would need JOIN query)
  
  return roles;
}

// Get active session from LoginIng table
async function getSessionId(userId: string): Promise<string> {
  const [session] = await mysqlPool.query(
    'SELECT SessionID FROM LoginIng WHERE ID = ? ORDER BY RegDate DESC LIMIT 1',
    [userId]
  );
  return session?.SessionID || '';
}
```

---

### **Option 2: Batch Sync (Fallback)**

```typescript
// Periodic sync every 1-5 minutes
import { CronJob } from 'cron';

const syncJob = new CronJob('*/1 * * * *', async () => {
  console.log('Starting user batch sync...');
  
  // Get all active sessions from MySQL
  const activeSessions = await mysqlPool.query(`
    SELECT DISTINCT 
      l.ID, l.SessionID, l.IP, l.RegDate,
      m.Name, m.Email, m.MemberType, m.EduManager, m.TestID
    FROM LoginIng l
    JOIN Member m ON l.ID = m.ID
    WHERE m.UseYN = 'Y'
  `);
  
  // Bulk update MongoDB
  const bulkOps = activeSessions[0].map(session => ({
    updateOne: {
      filter: { userId: session.ID },
      update: {
        $set: {
          'currentSession.isOnline': true,
          'currentSession.sessionId': session.SessionID,
          'currentSession.ipAddress': session.IP,
          'currentSession.loginTime': session.RegDate,
          lastSync: new Date()
        }
      },
      upsert: true
    }
  }));
  
  if (bulkOps.length > 0) {
    await mongoDb.collection('users').bulkWrite(bulkOps);
    console.log(`Synced ${bulkOps.length} active users`);
  }
  
  // Mark offline users
  await mongoDb.collection('users').updateMany(
    { 
      userId: { $nin: activeSessions[0].map(s => s.ID) },
      'currentSession.isOnline': true
    },
    { 
      $set: { 
        'currentSession.isOnline': false,
        lastSync: new Date()
      } 
    }
  );
});

syncJob.start();
```

---

## 🎯 **DUPLICATE NAME HANDLING**

### **Display Strategy (Frontend)**

```typescript
// React Component Example
interface UserDisplayProps {
  user: MongoDBUser;
}

function UserCard({ user }: UserDisplayProps) {
  // Strategy 1: Show ID in parentheses
  const displayName = `${user.name} (${user.userId})`;
  
  // Strategy 2: Show role badge
  const roleBadge = user.isEduManager ? '관리자' : '수강생';
  
  // Strategy 3: Show department
  const deptName = user.departmentId ? getDepartmentName(user.departmentId) : '';
  
  return (
    <div className="user-card">
      <h3>{displayName}</h3>
      <span className="badge">{roleBadge}</span>
      {deptName && <span className="dept">{deptName}</span>}
      <p>Email: {user.email}</p>
    </div>
  );
}

// Search with disambiguation
async function searchUsers(nameQuery: string) {
  const users = await mongoDb.collection('users').find({
    name: { $regex: nameQuery, $options: 'i' },
    isActive: true,
    isWithdrawn: false
  }).toArray();
  
  // Group by name if duplicates exist
  const nameGroups = groupBy(users, 'name');
  
  return Object.entries(nameGroups).map(([name, userList]) => ({
    name,
    count: userList.length,
    users: userList.map(u => ({
      ...u,
      displayName: `${u.name} (${u.userId})${u.isEduManager ? ' - 관리자' : ''}`
    }))
  }));
}
```

---

## 📈 **CAPACITY RECOMMENDATIONS**

### **Scaling for 10,000+ Concurrent Users:**

```yaml
# System Architecture

## MySQL (Legacy - Read-Only)
- Purpose: Source of truth for authentication
- Connections: Increase max_connections to 5,000
- Read Replicas: 2-3 read replicas for load balancing
- Connection Pooling: mysql2 with 100-200 pool size

## MongoDB (New - Read/Write)
- Purpose: Real-time session tracking, analytics, caching
- Connections: 50,000+ (MongoDB handles this better)
- Sharding: User collection sharded by userId hash
- Replica Set: 3 nodes minimum (1 primary, 2 secondary)

## Redis (Session Store)
- Purpose: Real-time session state, PubSub events
- Connections: Unlimited (virtually)
- Cluster Mode: 6 nodes (3 master, 3 replica)
- Use Cases:
  - Session storage (replace PHP sessions)
  - Real-time online user count
  - Live chat message queue
  - Video room participant tracking

## LiveKit (Video Streaming)
- Purpose: Real-time video/audio
- Rooms: Unlimited
- Concurrent Participants: 100 per room (default)
- Can handle 10,000+ concurrent users across multiple rooms

## Load Balancing
- Nginx: Round-robin to 4-8 Node.js app servers
- PM2 Cluster Mode: 4-8 processes per server
- Total Capacity: 40,000+ concurrent connections
```

---

## 🔐 **SECURITY: Preventing User Mismatch**

```typescript
// 1. UNIQUE CONSTRAINT at Database Level
db.users.createIndex({ userId: 1 }, { unique: true });
db.users.createIndex({ email: 1 }, { unique: true });

// 2. Transaction-safe upsert
async function safeUpsertUser(user: MongoDBUser) {
  const session = mongoClient.startSession();
  try {
    await session.withTransaction(async () => {
      // Check for conflicts
      const existing = await db.users.findOne({ 
        $or: [
          { userId: user.userId },
          { email: user.email }
        ]
      }, { session });
      
      if (existing && existing.userId !== user.userId) {
        throw new Error('Email already registered to different user');
      }
      
      // Atomic upsert
      await db.users.updateOne(
        { userId: user.userId },
        { $set: user },
        { upsert: true, session }
      );
    });
  } finally {
    await session.endSession();
  }
}

// 3. Query by ID, never by name alone
async function getUserById(userId: string) {
  return await db.users.findOne({ userId }); // ✅ Safe
}

async function getUserByName(name: string) {
  // ❌ Dangerous - returns first match only
  // return await db.users.findOne({ name });
  
  // ✅ Safe - returns all matches with disambiguation
  const users = await db.users.find({ name }).toArray();
  if (users.length > 1) {
    console.warn(`Multiple users found with name: ${name}`);
  }
  return users;
}

// 4. LiveKit room participant uniqueness
async function joinLiveKitRoom(userId: string, roomName: string) {
  const user = await getUserById(userId); // Use ID, not name
  
  const token = await createLiveKitToken({
    identity: user.userId,          // ✅ Unique identifier
    name: user.displayName,         // Display name (can duplicate)
    metadata: JSON.stringify({
      userIdx: user.userIdx,
      roles: user.roles,
      email: user.email
    })
  });
  
  return token;
}
```

---

## 📊 **MONITORING & ANALYTICS**

```typescript
// Real-time dashboard queries
interface SystemStats {
  totalUsers: number;
  onlineUsers: number;
  activeRooms: number;
  usersByRole: Record<string, number>;
  duplicateNames: Array<{ name: string; count: number }>;
}

async function getSystemStats(): Promise<SystemStats> {
  const [
    totalUsers,
    onlineUsers,
    usersByRole,
    duplicateNames
  ] = await Promise.all([
    // Total registered users
    db.users.countDocuments({ isActive: true, isWithdrawn: false }),
    
    // Currently online
    db.users.countDocuments({ 'currentSession.isOnline': true }),
    
    // Group by role
    db.users.aggregate([
      { $match: { isActive: true } },
      { $unwind: '$roles' },
      { $group: { _id: '$roles', count: { $sum: 1 } } }
    ]).toArray(),
    
    // Find duplicate names
    db.users.aggregate([
      { $match: { isActive: true } },
      { $group: { _id: '$name', count: { $sum: 1 } } },
      { $match: { count: { $gt: 1 } } },
      { $sort: { count: -1 } },
      { $limit: 100 }
    ]).toArray()
  ]);
  
  return {
    totalUsers,
    onlineUsers,
    activeRooms: await getActiveRoomCount(),
    usersByRole: Object.fromEntries(usersByRole.map(r => [r._id, r.count])),
    duplicateNames: duplicateNames.map(d => ({ name: d._id, count: d.count }))
  };
}
```

---

## ✅ **IMPLEMENTATION CHECKLIST**

- [ ] **Phase 1: MongoDB Setup**
  - [ ] Install MongoDB (Atlas or self-hosted)
  - [ ] Create `users` collection
  - [ ] Set up unique indexes (userId, email)
  - [ ] Configure replica set for high availability

- [ ] **Phase 2: Sync Service**
  - [ ] Install Redis for PubSub
  - [ ] Create Node.js sync service
  - [ ] Implement event-driven sync on login/logout
  - [ ] Add batch sync fallback (every 1 min)

- [ ] **Phase 3: API Integration**
  - [ ] Create REST/GraphQL API for user queries
  - [ ] Implement WebSocket for real-time updates
  - [ ] Add authentication middleware
  - [ ] Rate limiting and security

- [ ] **Phase 4: Frontend**
  - [ ] Display users with disambiguation (Name + ID)
  - [ ] Real-time online status indicator
  - [ ] Search with duplicate name handling
  - [ ] Role-based UI rendering

- [ ] **Phase 5: LiveKit Integration**
  - [ ] Create LiveKit rooms with userId as identity
  - [ ] Sync participant metadata to MongoDB
  - [ ] Real-time participant tracking
  - [ ] Recording and analytics

- [ ] **Phase 6: Monitoring**
  - [ ] Set up Grafana + Prometheus
  - [ ] Real-time user count dashboard
  - [ ] Alert on duplicate name conflicts
  - [ ] Performance monitoring

---

## 🎯 **FINAL ANSWER TO YOUR QUESTIONS**

### **Q1: How many users can enter?**

**Current (PHP/MySQL):**
- **500-2,000 concurrent users** (typical MySQL setup)
- Check actual limit: `SHOW VARIABLES LIKE 'max_connections';`

**With MongoDB Migration:**
- **10,000-50,000+ concurrent users** (with proper scaling)
- MongoDB handles connections better than MySQL
- Use connection pooling and load balancing

**LiveKit Video Rooms:**
- **100 participants per room** (default)
- **Unlimited rooms** (can have 100+ rooms simultaneously)
- Total capacity: **10,000+ concurrent video users**

---

### **Q2: Can we send roles and names to MongoDB in real-time?**

**✅ YES! Two methods:**

**Method 1: Event-Driven (Recommended)**
```typescript
// On every login in PHP, trigger:
POST /api/sync/user-login
{
  "userId": "kim123",
  "sessionId": "abc123xyz",
  "ipAddress": "1.2.3.4"
}

// Node.js service syncs to MongoDB immediately
// Redis PubSub notifies all connected clients
// WebSocket pushes update to frontend
```

**Method 2: Batch Sync**
```typescript
// Cron job runs every 1 minute
// Queries LoginIng table for active sessions
// Bulk updates MongoDB users collection
// Marks offline users
```

**Both methods can run simultaneously for redundancy!**

---

### **Q3: Will same-name people mismatch in MongoDB?**

**✅ NO! Here's how we prevent it:**

1. **Unique Indexes:**
```javascript
// MongoDB enforces uniqueness by ID, not name
userId: "kim123" → UNIQUE ✅
userId: "kim456" → UNIQUE ✅
name: "김철수" → NOT unique (allowed) ✅
```

2. **Composite Keys:**
```javascript
User 1: { userId: "kim123", name: "김철수", email: "kim1@email.com" }
User 2: { userId: "kim456", name: "김철수", email: "kim2@email.com" }
User 3: { userId: "kim789", name: "김철수", email: "kim3@email.com" }

// All three can coexist without conflict!
// MongoDB uses userId or email for lookups, not name
```

3. **Display Disambiguation:**
```typescript
// Frontend shows:
"김철수 (kim123)" - Student
"김철수 (kim456)" - Admin
"김철수 (kim789)" - Manager

// Or with role badges:
"김철수" [수강생] kim123
"김철수" [관리자] kim456
"김철수" [영업자] kim789
```

4. **LiveKit Identity:**
```typescript
// Video room uses userId as identity, not name
livekit.joinRoom({
  identity: "kim123",           // ✅ Unique
  name: "김철수 (kim123)",        // Display name
  metadata: { roles: ["student"] }
})

// No collision possible!
```

---

## 🚀 **RECOMMENDATION**

**For best performance with 10,000+ users:**

```yaml
Architecture:
  MySQL (Legacy): Authentication only, read-mostly
  MongoDB: Session tracking, user cache, analytics
  Redis: Real-time events, online status, PubSub
  LiveKit: Video rooms and real-time communication
  
Sync Strategy:
  Primary: Event-driven on login/logout
  Backup: Batch sync every 1 minute
  Real-time: WebSocket for live updates
  
Unique Keys:
  Primary: userId (from MySQL Member.ID)
  Secondary: email, userIdx
  Display: name + userId + role badge
  
Capacity:
  Current: 500-2,000 users
  Target: 10,000-50,000 users
  Video: 100 per room × unlimited rooms
```

**No user mismatches will occur if you follow the unique index strategy!** 🎯

