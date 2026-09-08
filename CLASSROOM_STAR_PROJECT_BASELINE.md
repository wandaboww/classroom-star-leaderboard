# CLASSROOM STAR
## Project Concept & Product Requirements Baseline

**Status:** LOCKED  
**Document Version:** 1.0  
**Date:** 2026-09-07  
**Purpose:** Baseline specification for AI Agent development

---

## 1. Project Overview

**Classroom Star** is a responsive web application for classroom participation gamification.

The application allows teachers to award stars (⭐) to students during teaching sessions as a simple reward for active participation, communication, contribution, and engagement.

Stars accumulate during an academic period/semester and can later be used to determine a student level/category and an additional score.

### Core principle

> Participation → Star Reward → Accumulated Stars → Level/Category → Additional Score

The application must prioritize speed and simplicity because teachers will use it while actively teaching.

---

## 2. Locked Product Concept

The following decisions are locked and should be treated as the project's baseline unless the project owner explicitly requests a change.

### 2.1 Star system

- Stars are direct reward points.
- There is **no activity/category system** for awarding stars.
- A teacher simply chooses how many stars to give.
- The amount can vary for every award.
- Example: 1, 2, 3, 4, or 5 stars.
- The application must not require the teacher to select an activity/reason for every star award.

### 2.2 Quick Award flow

The locked award flow is:

```text
Select Class
    ↓
Select Number of Stars
    ↓
Select Student Name
    ↓
Award Stars
```

Example:

```text
Class: XI IPA 1
Stars: ⭐⭐⭐
Student: Citra

→ Award +3 Stars
```

The student's total is updated immediately.

### 2.3 Multiple users

The application is multi-user.

Supported roles:

1. Admin
2. Teacher
3. Student

There may be multiple teacher accounts.

### 2.4 Admin

Admin has full access to manage the system and all major data.

Admin capabilities include:

- User management
- Teacher management
- Student management
- Class management
- Semester/period management
- Star configuration management
- Level/category management
- Leaderboard access
- Report access
- XLSX import/export
- Full CRUD operations
- System configuration

### 2.5 Teacher

Teachers have individual accounts.

Teacher capabilities include:

- Login
- Access assigned classes
- Manage permitted student data
- Award stars
- View leaderboard
- View Report Star Activity
- View student progress
- Import/export permitted student data
- Perform permitted CRUD operations

Teachers must not have unrestricted administrative access.

### 2.6 Student

Students have individual accounts.

Student capabilities include:

- Login
- View own total stars
- View own level/category
- View leaderboard
- View own Report Star Activity
- View own progress

Students cannot modify star transactions or other protected data.

---

## 3. Leaderboard

The leaderboard ranks students by total stars.

Primary displayed fields:

| Full Name | Class | Star Count |
|---|---|---:|
| Citra | XI IPA 1 | ⭐ 76 |
| Andi | XI IPA 1 | ⭐ 68 |
| Farhan | XI IPA 1 | ⭐ 61 |

The leaderboard must be dynamic and respect user permissions.

Possible views:

- Admin: all relevant classes/data
- Teacher: classes/data they are permitted to access
- Student: permitted leaderboard view

The exact privacy scope can be finalized during authorization design, but students must not gain access to protected administrative data.

---

## 4. Report Star Activity

The feature is named exactly:

> **Report Star Activity**

It records every star-awarding transaction.

Core fields:

| Date | Full Name | Class | Star Count |
|---|---|---|---:|
| 2026-09-01 | Citra | XI IPA 1 | ⭐⭐⭐ |
| 2026-09-01 | Andi | XI IPA 1 | ⭐ |
| 2026-09-03 | Citra | XI IPA 1 | ⭐⭐ |

For system/audit purposes, the transaction should also retain the teacher/user who performed the award, even if that field is not part of the primary student-facing table.

Recommended transaction information:

- Transaction ID
- Timestamp
- Student ID
- Class ID
- Teacher/User ID
- Star amount
- Semester/period ID
- Created timestamp

The report should support filtering where appropriate, such as:

- Date
- Class
- Student
- Teacher
- Semester/period

---

## 5. Level / Category System

At the end of a semester/period, accumulated stars are converted into a level/category.

Initial proposed structure:

| Achievement | Level | Predicate |
|---:|---|---|
| 0–19% | Level 1 | Very Low |
| 20–39% | Level 2 | Low |
| 40–59% | Level 3 | Fair |
| 60–74% | Level 4 | Good |
| 75–89% | Level 5 | Very Good |
| 90–100% | Level 6 | Outstanding |

Important:

- This table is the **initial baseline**, not an immutable final threshold.
- Thresholds and names should be configurable by Admin.
- The final definition of “maximum star opportunity” must be designed carefully before implementing percentage-based evaluation.

---

## 6. Semester / Final Additional Score

Stars may be converted into an additional score at the end of a semester.

Initial proposed mapping:

| Level | Additional Score |
|---|---:|
| Level 1 | +0 |
| Level 2 | +1 |
| Level 3 | +2 |
| Level 4 | +3 |
| Level 5 | +4 |
| Level 6 | +5 |

Example:

```text
Academic Score:       82
Participation Bonus:  +4
Final Score:          86
```

The star-based score is intended as an **additional participation/reward component**, not a replacement for academic assessment.

The final calculation rules must be configurable and should be clearly documented before production use.

---

## 7. Star Percentage Calculation

The initial conceptual formula is:

```text
Student Star Achievement %
=
(Student Total Stars / Maximum Star Opportunity) × 100
```

Example:

```text
Student Stars = 76
Maximum Opportunity = 100

Achievement = 76%
```

This produces the level/category.

### Important design issue

The system must define how `Maximum Star Opportunity` is calculated.

Because teachers can award variable amounts of stars, a fixed threshold such as "100 stars per semester" may not always be fair.

This should be resolved during the scoring-rule design phase.

Do not silently invent a final scoring methodology without project-owner approval.

---

## 8. Student Data: XLSX Import / Export

Student lists must support Excel `.xlsx` import/export.

### Import

Teachers/Admin should be able to upload an XLSX file rather than manually entering every student.

Initial template:

| NIS | Full Name | Class |
|---|---|---|
| 001 | Andi Saputra | XI IPA 1 |
| 002 | Budi Santoso | XI IPA 1 |
| 003 | Citra Lestari | XI IPA 1 |

Import flow:

```text
Upload XLSX
    ↓
Read File
    ↓
Validate Data
    ↓
Show Validation Result / Preview
    ↓
Confirm Import
    ↓
Save to Database
```

The system should validate:

- Required columns
- Empty required fields
- Duplicate student identifiers
- Invalid class references
- Invalid file format
- Data consistency

### Export

Student data can be exported to `.xlsx`.

Depending on permission, exports may include:

- NIS
- Full Name
- Class
- Total Stars
- Level
- Additional Score

The exact export columns can be configurable later.

---

## 9. CRUD Requirements

Major entities must support CRUD where appropriate.

CRUD means:

> Create → Read → Update → Delete

Primary entities:

### Users

- Create
- Read
- Update
- Delete

### Teachers

- Create
- Read
- Update
- Delete

### Students

- Create
- Read
- Update
- Delete
- XLSX Import
- XLSX Export

### Classes

- Create
- Read
- Update
- Delete

### Semesters / Periods

- Create
- Read
- Update
- Delete

### Level / Category Configuration

- Create
- Read
- Update
- Delete

### Star Transactions

Star transactions should generally be treated as an audit/history record.

The implementation should avoid unrestricted destructive deletion because deleting a transaction can alter historical scoring.

If correction is required, prefer an auditable correction/reversal mechanism rather than silently deleting historical records.

---

## 10. Role & Permission Matrix

Initial permission concept:

| Feature | Admin | Teacher | Student |
|---|:---:|:---:|:---:|
| Login | ✅ | ✅ | ✅ |
| User Management | ✅ | ❌ | ❌ |
| Teacher Management | ✅ | ❌ | ❌ |
| Student Management | ✅ | Limited | ❌ |
| Class Management | ✅ | Limited | ❌ |
| Give Stars | Optional | ✅ | ❌ |
| Leaderboard | ✅ | ✅ | ✅ |
| Report Star Activity | ✅ | Limited | Own |
| View Level | ✅ | ✅ | Own |
| Semester Management | ✅ | ❌ | ❌ |
| Level Configuration | ✅ | ❌ | ❌ |
| XLSX Import | ✅ | Limited | ❌ |
| XLSX Export | ✅ | Limited | ❌ |
| System Settings | ✅ | ❌ | ❌ |

`Limited` means access is restricted to data assigned to or managed by the teacher.

Exact permission granularity should be finalized during authorization/database design.

---

## 11. Responsive Design

The application must be responsive.

Supported device contexts:

- Desktop/Laptop
- Tablet
- Smartphone
- Projector/TV display

### Teacher desktop

Optimized for managing many students at once.

### Teacher mobile

Optimized for fast star awarding during class.

The core action should remain easy:

```text
Class → Stars → Student → Award
```

### Projector/TV

A large-screen leaderboard mode can be provided.

---

## 12. Dynamic Student Access

Student access must be account-based.

A student should see their own information after login.

### Student dashboard concept

```text
My Stars
⭐ 76

My Level
Level 5

Leaderboard
[View]

Report Star Activity
[View]
```

The student must not be able to modify protected scoring data.

---

## 13. Core Application Structure

Conceptual navigation:

### Admin

```text
Admin Dashboard
├── Users
├── Teachers
├── Students
├── Classes
├── Semesters / Periods
├── Star Configuration
├── Levels
├── Leaderboard
├── Report Star Activity
└── Settings
```

### Teacher

```text
Teacher Dashboard
├── My Classes
├── Give Stars
├── Leaderboard
├── Report Star Activity
├── Students
└── Student Progress
```

### Student

```text
Student Dashboard
├── My Stars
├── My Level
├── Leaderboard
└── Report Star Activity
```

---

## 14. MVP Scope

The first version should focus on the core workflow.

### Authentication

- Login
- Role-based access
- Admin
- Teacher
- Student

### User Management

- CRUD users
- Role assignment

### Class Management

- CRUD classes
- Teacher/class relationship

### Student Management

- CRUD students
- Class assignment
- XLSX import
- XLSX export

### Star System

- Select class
- Select star amount
- Select student
- Award stars
- Update total

### Leaderboard

- Rank by total stars
- Dynamic by role/class permission

### Report

- Report Star Activity
- Transaction history
- Filtering

### Level

- Calculate level/category
- Display level

### Semester

- Manage academic period/semester
- Associate star transactions with period

---

## 15. Future Development

After the MVP is stable, possible enhancements include:

```text
MVP
 ↓
Student Dashboard
 ↓
Teacher Dashboard
 ↓
Admin Dashboard
 ↓
Leaderboard
 ↓
Report Star Activity
 ↓
Level & Achievement
 ↓
Semester Evaluation
 ↓
Automatic Score Conversion
 ↓
Analytics
 ↓
Printable / Exportable Reports
```

Potential features:

- Achievement badges
- Progress charts
- Class statistics
- Advanced filtering
- PDF reports
- Print reports
- Semester comparison
- More detailed analytics

These features are future scope and should not unnecessarily expand the MVP.

---

## 16. Core Data Model Concept

Initial conceptual entities:

```text
USERS
  │
  ├── ADMIN
  ├── TEACHER
  └── STUDENT
        │
        ↓
     CLASSES
        │
        ↓
STAR_TRANSACTIONS
        │
        ├── Teacher/User
        ├── Student
        ├── Class
        ├── Semester
        ├── Star Amount
        └── Timestamp

SEMESTERS
LEVELS
```

A more detailed relational design/ERD must be created before implementation.

---

## 17. Core Business Rules

1. Only authorized teachers can award stars.
2. Students cannot award or modify stars.
3. Every star award must create a transaction/history record.
4. A star transaction should be associated with a student, class, teacher/user, and semester/period.
5. Total stars should be derived safely from valid transactions or maintained with a reliable consistency strategy.
6. Leaderboard ranking is based on total stars for the relevant scope/period.
7. Level is determined from the configured scoring rules.
8. Additional score is determined from the configured level/scoring rules.
9. Admin has the broadest system permissions.
10. Multiple teachers are supported.
11. Teachers can only access data allowed by their assignments/permissions.
12. Students can access their own protected data.
13. Student bulk data import/export uses `.xlsx`.
14. No activity/reason category is required when awarding stars.
15. The star-award interaction must be fast enough for real-time classroom use.
16. Historical scoring records should remain auditable.

---

## 18. UX Principles

The product should follow these principles:

### Simple

Avoid unnecessary forms and configuration during teaching.

### Fast

The star-award workflow should take only a few interactions.

### Visual

Stars, totals, levels, and rankings should be immediately understandable.

### Motivating

Students should be able to see their progress and classroom ranking where permitted.

### Measurable

Semester-end results should be based on recorded data.

### Auditable

Changes to scoring data should be traceable.

---

## 19. Recommended Development Order

AI agents should implement the system in stages.

### Phase 1 — Foundation

- Project setup
- Database setup
- Authentication
- Role system
- Basic layout
- Responsive design system

### Phase 2 — Master Data

- Users
- Teachers
- Students
- Classes
- Teacher/class assignment
- Semester/period

### Phase 3 — Star System

- Award workflow
- Star transactions
- Total calculation
- Validation
- Transaction history

### Phase 4 — Views

- Teacher dashboard
- Student dashboard
- Admin dashboard
- Leaderboard
- Report Star Activity

### Phase 5 — XLSX

- Import template
- XLSX validation
- Import preview
- Import execution
- Export

### Phase 6 — Evaluation

- Level configuration
- Percentage calculation
- Semester evaluation
- Additional score

### Phase 7 — QA

- Role/permission testing
- Star calculation testing
- XLSX testing
- Responsive testing
- Data integrity testing
- Security testing

---

## 20. Important Decisions Still To Be Finalized

The product concept is locked, but some implementation details should be explicitly decided before production development.

### 20.1 Maximum Star Opportunity

How should the system determine the maximum possible stars for percentage-based semester evaluation?

Possible approaches:

- Fixed maximum per meeting
- Fixed maximum per semester
- Teacher-defined maximum
- Opportunity-based calculation
- Another approved formula

Do not choose one silently.

### 20.2 Star Correction

Determine whether teachers can:

- Remove stars
- Correct a mistaken award
- Reverse a transaction

Recommended approach: use an auditable correction/reversal rather than deleting history.

### 20.3 Class Ownership

Define whether:

- One teacher can teach multiple classes
- One class can have multiple teachers
- Admin assigns teachers to classes
- Teachers can create their own classes

The data model should support the final decision.

### 20.4 Student Identity

Define the unique student identifier.

Recommended candidate:

- NIS or school-specific student ID

Avoid using full name as the unique identifier.

### 20.5 Semester Lifecycle

Define states such as:

```text
Draft → Active → Closed → Archived
```

A closed semester should preserve historical results.

### 20.6 Student Leaderboard Privacy

Define whether students can see:

- Entire class ranking
- Only their own position
- Multiple classes
- All students in the school

This should be a configurable/privacy decision.

---

# 21. Product Definition

## Product Name

**Classroom Star**

## Product Type

Responsive web application.

## Primary Users

- Admin
- Teacher
- Student

## Primary Purpose

Reward classroom participation with stars and convert accumulated stars into measurable semester-level participation results.

## Core Interaction

```text
Select Class
→ Select Star Amount
→ Select Student
→ Award Star
```

## Core Output

```text
Star Total
→ Leaderboard
→ Level
→ Additional Score
→ Report Star Activity
```

## Data Input

- Manual CRUD
- XLSX student import

## Data Output

- Leaderboard
- Reports
- XLSX export
- Semester evaluation

---

# 22. Instruction for AI Agent

Treat this document as the **locked product baseline**.

When developing Classroom Star:

1. Do not introduce an activity/reason category into the star-awarding workflow unless explicitly requested.
2. Do not replace the locked award flow with a more complicated workflow.
3. Preserve Admin, Teacher, and Student role separation.
4. Support multiple teachers.
5. Use CRUD for the defined master data.
6. Support `.xlsx` import/export for student data.
7. Keep star transactions auditable.
8. Keep the application responsive.
9. Prioritize fast classroom interaction.
10. Do not make assumptions about unresolved business rules; flag them for confirmation.
11. Keep future features separate from MVP unless explicitly requested.
12. Before making architecture-changing decisions, check this document against the requested change.

---

## 23. Locked Status

**CLASSROOM STAR CONCEPT: LOCKED**

This document represents the current agreed product baseline.

Any future change should be treated as a **change request** and should explicitly identify:

- What is being changed
- Why it is being changed
- What existing requirement it affects
- Whether related database, UI, permission, or scoring rules must also change

