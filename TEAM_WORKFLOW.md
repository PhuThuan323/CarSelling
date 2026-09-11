# 🏢 GitHub Team Development Guide - CarSelling

## 📋 Table of Contents
1. [Branching Strategy](#branching-strategy)
2. [Folder Structure](#folder-structure)
3. [Team Workflow](#team-workflow)
4. [GitHub Configuration](#github-configuration)
5. [Code Review Process](#code-review-process)
6. [Collaboration Rules](#collaboration-rules)
7. [Documentation Standards](#documentation-standards)

---

## 🌿 **Branching Strategy (Git Flow)**

### **Branch Structure**

```
main (Production Ready)
  ↑
  ├─ release/v1.0.0 (Release prep)
  ├─ develop (Development)
  │   ├─ feature/authentication (Team Member 1)
  │   ├─ feature/payment (Team Member 2)
  │   ├─ feature/dashboard (Team Member 3)
  │   └─ bugfix/cart-issue (Team Member 1)
  └─ hotfix/security-patch (Quick production fix)
```

### **Branch Naming Convention**

```
feature/feature-name          # New features
bugfix/bug-description        # Bug fixes
hotfix/urgent-fix             # Production fixes
release/v1.0.0                # Release preparation
develop                       # Integration branch
main                          # Production branch
```

### **Branch Purpose**

| Branch | Purpose | Who | When to Create |
|--------|---------|-----|-----------------|
| **main** | Production code only | Leader | Never directly; merge from release |
| **develop** | Integration branch | Team | Base for all features |
| **feature/*** | New features | Developers | When starting new feature |
| **bugfix/*** | Bug fixes | Developers | When fixing bugs |
| **hotfix/*** | Emergency fixes | Leader | Critical production issues |
| **release/*** | Version release | Leader | Before releasing to production |

---

## 📁 **Recommended Folder Structure**

```
CarSelling/
│
├── app/                          # Backend code
│   ├── Controllers/
│   │   ├── AuthController.php        # Team Member 1
│   │   ├── ProductController.php     # Team Member 2
│   │   ├── CartController.php        # Team Member 1
│   │   ├── PaymentController.php     # Team Member 3
│   │   └── AdminController.php       # Team Member 4
│   ├── Models/
│   │   ├── User.php                  # Team Member 1
│   │   ├── Product.php               # Team Member 2
│   │   ├── Order.php                 # Team Member 3
│   │   └── Payment.php               # Team Member 3
│   ├── Core/
│   │   ├── Database.php              # Shared
│   │   ├── Router.php                # Shared
│   │   ├── Auth.php                  # Team Member 1
│   │   └── Mail.php                  # Shared
│   ├── Middleware/
│   │   ├── AuthMiddleware.php        # Team Member 1
│   │   └── AdminMiddleware.php       # Team Member 1
│   ├── Services/
│   │   ├── PaymentService.php        # Team Member 3
│   │   ├── EmailService.php          # Team Member 4
│   │   └── ImageService.php          # Team Member 2
│   └── helpers.php                   # Shared utilities
│
├── public/                       # Frontend entry point
│   ├── index.php                 # Main router
│   └── assets/
│       ├── css/
│       │   ├── style.css             # Team Member 5
│       │   ├── responsive.css        # Team Member 5
│       │   └── components.css        # Team Member 5
│       ├── js/
│       │   ├── main.js               # Team Member 5
│       │   ├── cart.js               # Team Member 5
│       │   ├── auth.js               # Team Member 5
│       │   └── payment.js            # Team Member 5
│       ├── images/
│       ├── fonts/
│       └── vendor/                   # Third-party libraries
│
├── templates/                    # Smarty templates
│   ├── layouts/
│   │   ├── main.tpl                  # Base layout
│   │   ├── admin.tpl                 # Admin layout
│   │   └── auth.tpl                  # Auth pages layout
│   ├── auth/
│   │   ├── login.tpl                 # Team Member 1
│   │   └── register.tpl              # Team Member 1
│   ├── products/
│   │   ├── index.tpl                 # Team Member 2
│   │   ├── show.tpl                  # Team Member 2
│   │   └── edit.tpl                  # Team Member 2
│   ├── cart/
│   │   ├── index.tpl                 # Team Member 1
│   │   └── checkout.tpl              # Team Member 1
│   ├── admin/
│   │   ├── dashboard.tpl             # Team Member 4
│   │   ├── users.tpl                 # Team Member 4
│   │   └── products.tpl              # Team Member 4
│   └── errors/
│       ├── 404.tpl
│       └── 500.tpl
│
├── database/
│   ├── migrations/
│   │   ├── 2024_001_create_users_table.sql
│   │   ├── 2024_002_create_products_table.sql
│   │   └── 2024_003_create_orders_table.sql
│   ├── schema.sql                    # Current schema
│   └── seed.sql                      # Sample data
│
├── tests/                        # Unit & Integration tests
│   ├── Unit/
│   │   ├── AuthTest.php
│   │   └── ProductTest.php
│   └── Integration/
│       ├── CartTest.php
│       └── PaymentTest.php
│
├── docs/                         # Project documentation
│   ├── API.md                        # API documentation
│   ├── ARCHITECTURE.md               # System architecture
│   ├── DATABASE.md                   # Database schema
│   ├── SETUP.md                      # Setup instructions
│   └── CONTRIBUTING.md               # Contribution guidelines
│
├── config/
│   ├── app.php                       # App configuration
│   ├── database.php                  # Database config
│   └── mail.php                      # Email config
│
├── storage/
│   ├── cache/
│   ├── templates_c/
│   ├── logs/
│   └── uploads/
│
├── .github/
│   ├── PULL_REQUEST_TEMPLATE.md      # PR template
│   ├── ISSUE_TEMPLATE.md             # Issue template
│   └── workflows/                    # CI/CD pipelines
│       ├── tests.yml                 # Run tests on PR
│       └── deploy.yml                # Auto-deploy on merge
│
├── .gitignore
├── composer.json
├── README.md
├── CONTRIBUTING.md                   # Team guidelines
├── FRONTEND_GUIDE.md
└── CHANGELOG.md                      # Version history
```

---

## 👥 **Team Workflow**

### **1. Feature Development**

#### **Team Member Starts Feature**
```powershell
# 1. Update develop branch
git checkout develop
git pull origin develop

# 2. Create feature branch
git checkout -b feature/user-authentication

# 3. Make changes and commit
git add .
git commit -m "Add user authentication system"

# 4. Push feature branch
git push -u origin feature/user-authentication
```

#### **Team Member Pushes Updates**
```powershell
# Regular commits
git add .
git commit -m "Add login form validation"
git push origin feature/user-authentication
```

#### **Ready for Review**
```powershell
# Push final changes
git push origin feature/user-authentication

# Go to GitHub → Create Pull Request
# - Base: develop
# - Compare: feature/user-authentication
```

---

### **2. Code Review Process**

#### **Leader/Reviewer Reviews PR**
- Check code quality
- Test functionality
- Verify tests pass
- Request changes if needed
- Approve and merge

#### **PR Checklist Template** (in PULL_REQUEST_TEMPLATE.md)
```markdown
## Description
Brief description of changes

## Type of Change
- [ ] New Feature
- [ ] Bug Fix
- [ ] Documentation
- [ ] Style Update

## Testing
- [ ] Tested locally
- [ ] Unit tests pass
- [ ] Integration tests pass

## Checklist
- [ ] Code follows style guide
- [ ] No console errors
- [ ] Comments added for complex logic
- [ ] Documentation updated
```

---

### **3. Merging to Main**

#### **When Ready for Release**
```powershell
# Leader creates release branch
git checkout -b release/v1.0.0
git push -u origin release/v1.0.0

# Make final adjustments (version bumps, etc.)
# Then merge to main

# Merge release to main
git checkout main
git pull origin main
git merge release/v1.0.0
git tag v1.0.0
git push origin main --tags

# Merge back to develop
git checkout develop
git merge release/v1.0.0
git push origin develop
```

---

## ⚙️ **GitHub Configuration**

### **1. Protect Main Branch**

**Settings → Branches → Branch Protection Rules**

Add rule for `main`:
- ✅ Require pull request reviews (2 approvals)
- ✅ Require status checks to pass
- ✅ Require branches to be up to date
- ✅ Dismiss stale reviews
- ✅ Restrict who can push (Leader only)

---

### **2. Protect Develop Branch**

**Settings → Branches → Branch Protection Rules**

Add rule for `develop`:
- ✅ Require pull request reviews (1 approval)
- ✅ Require status checks to pass
- ✅ Allow force pushes (No)

---

### **3. Setup Teams**

**Settings → Member Privileges**

Create teams:
- **Backend Team** (access to app/, database/)
- **Frontend Team** (access to public/, templates/)
- **DevOps/Admin** (full access)
- **Leads** (manage releases)

---

### **4. Require Code Owners Review**

Create `CODEOWNERS` file:
```
# Backend
/app/Controllers/ @backend-lead
/app/Models/ @backend-lead
/database/ @backend-lead

# Frontend
/public/assets/ @frontend-lead
/templates/ @frontend-lead

# All
*.md @project-lead
.github/ @project-lead
```

---

## 📝 **Code Review Process**

### **PR Review Workflow**

```
Developer Creates PR
        ↓
Automated Tests Run (CI/CD)
        ↓
Code Owners Review
        ↓
Request Changes? → Developer Updates → Re-review
        ↓
        No
        ↓
Approved ✅
        ↓
Merge to develop
        ↓
Tests Pass
        ↓
Delete Feature Branch
```

### **Review Checklist**

- [ ] Code follows project standards
- [ ] No hardcoded credentials
- [ ] Proper error handling
- [ ] Tests included for new features
- [ ] Documentation updated
- [ ] No merge conflicts
- [ ] Follows naming conventions
- [ ] Performance acceptable
- [ ] Security reviewed

---

## 🤝 **Collaboration Rules**

### **✅ DO:**
```
✓ Create feature branches for all work
✓ Write descriptive commit messages
✓ Keep commits focused and atomic
✓ Request review before merging
✓ Pull latest develop before starting
✓ Communicate changes in team chat
✓ Tag versions on main branch
✓ Update CHANGELOG for releases
```

### **❌ DON'T:**
```
✗ Push directly to main or develop
✗ Merge own PRs without review
✗ Force push to shared branches
✗ Commit secrets or credentials
✗ Make large commits without breaking down
✗ Leave branches stale
✗ Ignore failed tests
✗ Push to someone else's feature branch without permission
```

---

## 📚 **Documentation Standards**

### **Required Documentation Files**

#### **CONTRIBUTING.md**
```markdown
# Contributing to CarSelling

## Getting Started
1. Fork repository
2. Create feature branch
3. Make changes
4. Write tests
5. Submit PR

## Branch Naming
- feature/description
- bugfix/description
- hotfix/description

## Commit Messages
- Imperative mood: "Add feature" not "Added feature"
- Reference issues: "Fix #123"
- Keep under 50 characters

## Code Style
- Follow PSR-12 for PHP
- Use camelCase for JS variables
- Comment complex logic
```

#### **API.md**
Document all API endpoints:
```markdown
## POST /cart/add
Adds product to shopping cart.

### Request
```json
{
  "product_id": 1,
  "quantity": 2
}
```

### Response
```json
{
  "status": "success",
  "message": "Item added to cart"
}
```
```

#### **ARCHITECTURE.md**
System overview, design patterns, data flow

#### **DATABASE.md**
Schema documentation, relationships, indexes

---

## 🔄 **Typical Team Workflow Example**

### **Day 1: Start Authentication Feature**
```powershell
# Team Member 1 starts
git checkout -b feature/user-authentication
# Makes changes...
git push -u origin feature/user-authentication
# Creates Pull Request on GitHub
```

### **Day 2: Review & Feedback**
```
Team Leader reviews PR
Requests changes:
  - "Add password validation"
  - "Add error messages"

Team Member 1 updates:
  git add .
  git commit -m "Add password validation and error messages"
  git push origin feature/user-authentication

Leader approves ✅
```

### **Day 3: Merge**
```powershell
# Merge to develop on GitHub
# Delete feature branch

# Team Member 1 syncs
git checkout develop
git pull origin develop
git branch -d feature/user-authentication
```

### **Week 2: Release Preparation**
```powershell
# Leader creates release
git checkout -b release/v1.0.0
# Bump version numbers
# Update CHANGELOG
git push -u origin release/v1.0.0

# After testing
# Merge to main
# Tag version
# Merge back to develop
```

---

## 📊 **GitHub Issues for Task Management**

### **Create Issues for:**
- [ ] Features to develop
- [ ] Bugs to fix
- [ ] Documentation needed
- [ ] Code refactoring

### **Issue Template**
```markdown
## Description
Clear description of the task

## Acceptance Criteria
- [ ] Task 1
- [ ] Task 2
- [ ] Task 3

## Assigned To
@username

## Priority
High / Medium / Low

## Related PRs
Links to related pull requests
```

---

## 📈 **Milestones for Releases**

Create milestones for versions:
- **v1.0.0 - MVP** (Core features)
- **v1.1.0 - Payment** (Payment integration)
- **v2.0.0 - Admin Dashboard** (Admin features)

Track progress and assign issues to milestones.

---

## 🚀 **CI/CD Pipeline (Optional)**

Create `.github/workflows/tests.yml`:
```yaml
name: Tests
on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: vendor/bin/phpunit
      - name: Run linter
        run: vendor/bin/phpcs
```

This automatically runs tests on every PR!

---

## 📋 **Team Setup Checklist**

- [ ] Create GitHub organization (optional)
- [ ] Add team members as collaborators
- [ ] Setup branch protection rules
- [ ] Create CODEOWNERS file
- [ ] Write CONTRIBUTING.md
- [ ] Create PR template
- [ ] Create issue template
- [ ] Setup CI/CD workflows
- [ ] Create project board
- [ ] Assign team members to repositories
- [ ] Schedule weekly sync meetings
- [ ] Create team documentation

---

## 🎯 **Quick Command Reference**

### **For Team Members**
```powershell
# Get latest code
git checkout develop
git pull origin develop

# Start new feature
git checkout -b feature/my-feature

# Work and commit
git add .
git commit -m "Add feature description"
git push origin feature/my-feature

# Create PR on GitHub and wait for review

# After merge, sync local
git checkout develop
git pull origin develop
git branch -d feature/my-feature
```

### **For Team Leader**
```powershell
# Review PR
# Check code and tests pass
# Request changes or approve

# Merge PR on GitHub

# Prepare release
git checkout -b release/v1.0.0
git push -u origin release/v1.0.0

# After testing
git checkout main
git merge release/v1.0.0
git tag v1.0.0
git push origin main --tags
git push origin main

# Merge back to develop
git checkout develop
git merge release/v1.0.0
git push origin develop
```

---

## 🔐 **Security Best Practices**

- [ ] Never commit `.env` files
- [ ] Use GitHub Secrets for sensitive data
- [ ] Enable two-factor authentication
- [ ] Review dependencies for vulnerabilities
- [ ] Require HTTPS for repository
- [ ] Limit admin access
- [ ] Audit access logs regularly
- [ ] Use SSH keys for team members

---

## 📞 **Communication**

- **Slack/Discord Channel**: #carSelling-dev
- **Weekly Standup**: Monday 10 AM
- **Code Review Target**: 24 hours
- **Release Cycle**: Bi-weekly sprints
- **Emergency Protocol**: Hotfix branches for production issues

---

## 📖 **Additional Resources**

- [Git Flow Guide](https://www.atlassian.com/git/tutorials/comparing-workflows/gitflow-workflow)
- [GitHub Flow Alternative](https://guides.github.com/introduction/flow/)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [CODEOWNERS Documentation](https://docs.github.com/en/repositories/managing-your-repositorys-settings-and-features/customizing-your-repository/about-code-owners)

---

**Good luck managing your team project! 🚀**
