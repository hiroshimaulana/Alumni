# Domain-Driven Design (DDD) Specification
## Istanbul University Faculty of Economics — Management Information Systems (MIS) Alumni Portal
### Subdomains: Career Services & Opportunities & Application Tracking

---

## 1. Strategic Domain Design

### 1.1 Domain Overview
The **Career Services & Mentorship Ecosystem** bridges Istanbul University MIS alumni, students, partner enterprises, and faculty administration. It empowers alumni and corporate recruiters to post verified internships and career openings, while enabling MIS graduates and students to submit job applications, track candidate statuses, and connect with prospective employers.

```
┌──────────────────────────────────────────────────────────────────────────────────────┐
│                            Istanbul University MIS Alumni Portal                     │
└──────────────────────────────────────────┬───────────────────────────────────────────┘
                                           │
         ┌─────────────────────────────────┼─────────────────────────────────┐
         ▼                                 ▼                                 ▼
┌─────────────────────────┐   ┌─────────────────────────┐   ┌─────────────────────────┐
│ Identity & Access (IAM) │   │ Career Services Context │   │ Application Tracking    │
│  (Users, RBAC, Profiles)│   │ (Job Postings, Company) │   │ (Submissions, Review)   │
└─────────────────────────┘   └─────────────────────────┘   └─────────────────────────┘
```

### 1.2 Bounded Contexts
1. **Identity & Access Management (IAM) Context**:
   - Manages core user authentication, user roles (`super_admin`, `faculty_admin`, `alumni`, `student`), and university verification.
2. **Career Services Context**:
   - Manages company identities, opportunity listings, requirements, employment categories, experience expectations, compensation bounds, and publication lifecycles.
3. **Application Tracking Context**:
   - Manages candidate submissions, resumes, cover letters, application review state machines, recruiter notes, and hiring notifications.

---

## 2. Tactical Domain Design

### 2.1 Aggregate: `JobPosting` (Career Services Context)

#### Aggregate Root: `JobPosting`
An entity that encapsulates all details, validation rules, and publication constraints for employment opportunities.

#### Value Objects:
* `EmploymentType`: `full_time` | `part_time` | `internship` | `contract` | `remote`
* `ExperienceLevel`: `entry` | `mid` | `senior` | `lead` | `executive`
* `JobStatus`: `draft` | `active` | `closed` | `archived`
* `Salary`: Optional positive decimal number (> 0).
* `ApplicationDeadline`: ISO 8601 Date (`YYYY-MM-DD`) indicating listing expiration

#### Domain Invariants & Rules:
1. Every job posting must be attributed to an authentic poster (`posted_by_user_id` referencing a valid `User`).
2. Title is required (max 120 characters) and a company must be specified. Description is optional.
3. If a deadline is defined, it must represent a valid future or current calendar date.
4. Only postings with `JobStatus::ACTIVE` accept new candidate submissions.
5. Deletion of a job posting cascades or soft-archives child applications according to regulatory data retention rules.

#### Entity Validation Rules
- **`title`**: Required, maximum 120 characters.
- **`Company`**: Required (`company_name` must be non-empty; `company_id` stays optional).
- **`salary`**: Optional. If provided, it must be a positive number.
- **`description`**: Optional (text string).

> These rules apply to `store` and `update` in both `JobPostingController` and `ApiJobPostingController`.

---

### 2.2 Aggregate: `Application` (Application Tracking Context)

#### Aggregate Root: `Application`
Represents an applicant's formal expression of interest and submission bundle for a specific `JobPosting`.

#### Value Objects:
* `ApplicationStatus`:
  * `submitted`: Application successfully delivered by candidate.
  * `under_review`: Candidate dossier opened by recruiter / hiring alumni.
  * `shortlisted`: Candidate passed initial screening and invited to interview.
  * `accepted`: Candidate received an offer or formal placement.
  * `rejected`: Application declined.
* `ResumeUrl`: Validated URI or secure relative path to candidate resume/CV document.
* `CoverLetter`: Candidate's statement of qualifications and personal narrative.

#### State Machine & Status Transitions:
```
           ┌──────────────┐
           │  submitted   │
           └──────┬───────┘
                  │
                  ▼
         ┌──────────────────┐
    ┌───►│   under_review   ├────┐
    │    └────────┬─────────┘    │
    │             │              │
    │             ▼              │
    │    ┌──────────────────┐    │
    │    │   shortlisted    │    │
    │    └────────┬─────────┘    │
    │             │              │
    ▼             ▼              ▼
┌────────┐   ┌─────────┐   ┌──────────┐
│rejected│   │accepted │   │ rejected │
└────────┘   └─────────┘   └──────────┘
```

#### Domain Invariants & Rules:
1. An application must reference both an existing `JobPosting` and an existing `User`.
2. A user cannot apply multiple times to the exact same job posting while an application is active (uniqueness constraint on `[job_posting_id, user_id]`).
3. An application cannot be submitted to a `JobPosting` whose status is `closed` or `archived`.
4. Status transitions are unidirectional towards terminal states (`accepted` or `rejected`).

---

## 3. Relational Database Schema Specification

### 3.1 `job_postings` Table DDL
```sql
CREATE TABLE IF NOT EXISTS `job_postings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `posted_by_user_id` INT NOT NULL,
    `company_name` VARCHAR(255) NOT NULL,
    `company_id` INT NULL,
    `title` VARCHAR(120) NOT NULL,
    `description` TEXT NULL,
    `employment_type` ENUM('full_time', 'part_time', 'internship', 'contract', 'remote') NOT NULL DEFAULT 'full_time',
    `location` VARCHAR(150) NOT NULL,
    `salary` DECIMAL(12,2) NULL,
    `experience_level` ENUM('entry', 'mid', 'senior', 'lead', 'executive') NOT NULL DEFAULT 'mid',
    `status` ENUM('draft', 'active', 'closed', 'archived') NOT NULL DEFAULT 'active',
    `requirements` TEXT NULL,
    `deadline` DATE NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_job_postings_posted_by` FOREIGN KEY (`posted_by_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `chk_job_postings_salary` CHECK (`salary` IS NULL OR `salary` > 0),
    INDEX `idx_job_postings_status` (`status`),
    INDEX `idx_job_postings_company_id` (`company_id`),
    INDEX `idx_job_postings_posted_by` (`posted_by_user_id`),
    INDEX `idx_job_postings_employment_type` (`employment_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.2 `applications` Table DDL
```sql
CREATE TABLE IF NOT EXISTS `applications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `job_posting_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `applicant_name` VARCHAR(150) NOT NULL,
    `applicant_email` VARCHAR(255) NOT NULL,
    `resume_url` VARCHAR(255) NULL,
    `cover_letter` TEXT NULL,
    `portfolio_url` VARCHAR(255) NULL,
    `status` ENUM('submitted', 'under_review', 'shortlisted', 'rejected', 'accepted') NOT NULL DEFAULT 'submitted',
    `feedback` TEXT NULL,
    `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_applications_job` FOREIGN KEY (`job_posting_id`) REFERENCES `job_postings`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_applications_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uniq_user_job_application` (`job_posting_id`, `user_id`),
    INDEX `idx_applications_job_id` (`job_posting_id`),
    INDEX `idx_applications_user_id` (`user_id`),
    INDEX `idx_applications_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 4. Route Architecture & API Endpoints

### 4.1 Direct Job Postings Endpoints
| Verb | Path | Description | Controller Action |
|---|---|---|---|
| `GET` | `/job-postings` | Web UI: Explore job listings & search filter | `JobPostingController::index` |
| `GET` | `/job-postings/create` | Web UI: Job creation form / modal | `JobPostingController::create` |
| `POST` | `/job-postings` | Web Action: Submit new job posting | `JobPostingController::store` |
| `GET` | `/job-postings/{id}` | Web UI: Detailed job profile & application button | `JobPostingController::show` |
| `POST` | `/job-postings/{id}/update` | Web Action: Update job details | `JobPostingController::update` |
| `POST` | `/job-postings/{id}/delete` | Web Action: Remove job listing | `JobPostingController::delete` |
| `GET` | `/api/job-postings` | REST API: List job postings (with query filters) | `ApiJobPostingController::index` |
| `POST` | `/api/job-postings` | REST API: Create new job posting | `ApiJobPostingController::store` |
| `GET` | `/api/job-postings/{id}` | REST API: Retrieve single job posting details | `ApiJobPostingController::show` |
| `PUT` | `/api/job-postings/{id}` | REST API: Full / partial update of job posting | `ApiJobPostingController::update` |
| `DELETE` | `/api/job-postings/{id}` | REST API: Delete job posting | `ApiJobPostingController::destroy` |

### 4.2 Nested Job Postings Endpoints
| Verb | Path | Description | Controller Action |
|---|---|---|---|
| `GET` | `/companies/{id}/job-postings` | Web UI: Listings by company ID | `JobPostingController::byCompany` |
| `GET` | `/api/companies/{id}/job-postings`| REST API: Listings filtered by company ID | `ApiJobPostingController::byCompany` |
| `GET` | `/users/{id}/job-postings` | Web UI: Listings published by user ID | `JobPostingController::byUser` |
| `GET` | `/api/users/{id}/job-postings` | REST API: Listings published by user ID | `ApiJobPostingController::byUser` |

### 4.3 Direct Applications Endpoints
| Verb | Path | Description | Controller Action |
|---|---|---|---|
| `GET` | `/applications` | Web UI: View user's submitted applications / review dashboard | `ApplicationController::index` |
| `GET` | `/applications/{id}` | Web UI: Single application profile & status timeline | `ApplicationController::show` |
| `POST` | `/applications/{id}/status` | Web Action: Update candidate application status | `ApplicationController::updateStatus` |
| `GET` | `/api/applications` | REST API: List all applications | `ApiApplicationController::index` |
| `POST` | `/api/applications` | REST API: Submit a candidate application | `ApiApplicationController::store` |
| `GET` | `/api/applications/{id}` | REST API: Get application by ID | `ApiApplicationController::show` |
| `PUT` | `/api/applications/{id}` | REST API: Update application details / status | `ApiApplicationController::update` |
| `DELETE` | `/api/applications/{id}` | REST API: Withdraw / delete application | `ApiApplicationController::destroy` |

### 4.4 Nested Applications Endpoints
| Verb | Path | Description | Controller Action |
|---|---|---|---|
| `GET` | `/job-postings/{id}/applications` | Web UI: Review all applications for a specific job | `ApplicationController::byJobPosting` |
| `POST` | `/job-postings/{id}/applications` | Web Action: Submit candidate application for a specific job | `ApplicationController::apply` |
| `GET` | `/api/job-postings/{id}/applications` | REST API: List all applications submitted for a job | `ApiApplicationController::byJobPosting` |
| `POST` | `/api/job-postings/{id}/applications` | REST API: Submit new application for a job | `ApiApplicationController::apply` |
| `GET` | `/users/{id}/applications` | Web UI: Applications submitted by a specific user | `ApplicationController::byUser` |
| `GET` | `/api/users/{id}/applications` | REST API: Applications submitted by a specific user | `ApiApplicationController::byUser` |
