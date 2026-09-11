# Contributing to CarSelling

Thank you for contributing to the CarSelling project! This guide will help you understand how to work effectively as part of our development team.

## 🎯 Getting Started

### Prerequisites
- PHP 8.0+
- Composer
- Git installed and configured
- GitHub account with team access

### Setup Your Development Environment
```powershell
# 1. Clone the repository
git clone https://github.com/YOUR_ORG/CarSelling.git
cd CarSelling

# 2. Install dependencies
composer install

# 3. Create .env file from example
copy .env.example .env

# 4. Setup local database
php -S localhost:8000 -t public

# 5. Create feature branch
git checkout -b feature/your-feature-name
```

---

## 📋 Branching Strategy

### Branch Naming Convention
```
feature/feature-name          # New features
bugfix/bug-description        # Bug fixes
hotfix/critical-fix           # Production hotfixes
release/v1.0.0                # Release branches
docs/documentation-update     # Documentation
refactor/code-cleanup         # Refactoring
```

### Branch Workflow
1. Always create a feature branch from `develop`
2. Never work directly on `main` or `develop`
3. Keep feature branches focused on single concerns
4. Delete local/remote branches after merging

```powershell
# Start new feature
git checkout develop
git pull origin develop
git checkout -b feature/my-feature

# Push to remote
git push -u origin feature/my-feature

# Update feature branch
git add .
git commit -m "Description of changes"
git push origin feature/my-feature
```

---

## 💬 Commit Messages

### Format
```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types
- **feat**: A new feature
- **fix**: A bug fix
- **docs**: Documentation changes
- **style**: Code style changes (formatting)
- **refactor**: Code refactoring
- **test**: Adding/updating tests
- **chore**: Dependency updates, build changes

### Examples
```
Good:
  feat(auth): add password reset functionality
  fix(cart): resolve quantity update issue #123
  docs(api): update endpoint documentation
  
Bad:
  Updated stuff
  Fixed bug
  WIP
```

### Reference Issues
```
# In commit message
git commit -m "Fix login validation #123"
git commit -m "Closes #123, fixes #124"
```

---

## 🔄 Pull Request Process

### Before Creating PR
- [ ] Branch is up to date with develop
- [ ] All tests pass locally
- [ ] Code follows project style guide
- [ ] No merge conflicts
- [ ] Meaningful commit history
- [ ] Documentation updated

### Creating a PR
1. Push feature branch to GitHub
2. Go to repository → Pull Requests → New PR
3. Base: `develop` | Compare: `feature/your-feature`
4. Fill out PR template completely
5. Add relevant labels (bug, feature, documentation, etc.)
6. Assign reviewers (at least 1 other team member)
7. Request review

### PR Description Template
```markdown
## Description
Brief description of what this PR does

## Type of Change
- [ ] New Feature
- [ ] Bug Fix
- [ ] Breaking Change
- [ ] Documentation Update
- [ ] Refactoring
- [ ] Performance Improvement

## Related Issue
Closes #123

## Testing
Describe how you tested the changes:
- [ ] Tested locally
- [ ] Added unit tests
- [ ] Added integration tests
- [ ] Manual testing steps

## Checklist
- [ ] Code follows style guide
- [ ] Self-review completed
- [ ] Comments added for complex logic
- [ ] Documentation updated
- [ ] No breaking changes
- [ ] Tests pass
- [ ] No console errors
- [ ] Database migrations included (if applicable)

## Screenshots (if applicable)
Add screenshots for UI changes

## Additional Context
Any additional information reviewers should know
```

### Code Review Guidelines
- [ ] Code is readable and maintainable
- [ ] Functions have single responsibility
- [ ] Proper error handling
- [ ] No hardcoded values or credentials
- [ ] Comments explain WHY, not WHAT
- [ ] Tests cover new functionality
- [ ] Performance impact is acceptable
- [ ] Security vulnerabilities are addressed

### Addressing Review Comments
```powershell
# Make requested changes
git add .
git commit -m "Address review comments"
git push origin feature/my-feature

# Mark conversation as resolved in GitHub
# (Don't dismiss manually, let developer do it after changes are made)
```

---

## 🧪 Testing Requirements

### Unit Tests
All new functions should have corresponding unit tests:
```php
// Example: tests/Unit/AuthTest.php
class AuthTest extends TestCase {
    public function testPasswordValidation() {
        $result = validatePassword('weak');
        $this->assertFalse($result);
    }
}
```

### Running Tests
```powershell
# Run all tests
vendor/bin/phpunit

# Run specific test
vendor/bin/phpunit tests/Unit/AuthTest.php

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/
```

### Integration Tests
Test interactions between components:
```php
class CartIntegrationTest extends TestCase {
    public function testAddProductToCart() {
        // Simulate user adding product to cart
        // Verify database updates
        // Check response
    }
}
```

---

## 📐 Code Style Guide

### PHP Code Style (PSR-12)
```php
<?php
// Namespaces and imports at top
namespace App\Controllers;

use App\Models\Product;
use App\Core\View;

// Class declaration
class ProductController {
    
    // Properties
    private $productModel;
    
    // Constructor
    public function __construct(Product $productModel) {
        $this->productModel = $productModel;
    }
    
    // Methods - camelCase
    public function index() {
        // Code here
    }
    
    // Private helper methods
    private function validateInput($data) {
        // Code here
    }
}
?>
```

### JavaScript Style (ES6+)
```javascript
// Use const/let, not var
const productId = 123;
let quantity = 1;

// Arrow functions
const addToCart = (id, qty) => {
    // Code here
};

// Template literals
const message = `Product ${id} added to cart`;

// Proper naming
const getUserById = (id) => { /* ... */ }; // Good
const getuser = (id) => { /* ... */ };    // Bad
```

### CSS Style
```css
/* Use meaningful class names */
.product-card { /* ... */ }
.btn-primary { /* ... */ }
.cart-summary { /* ... */ }

/* Organize properties logically */
.element {
    /* Layout */
    display: flex;
    flex-direction: column;
    
    /* Sizing */
    width: 100%;
    height: auto;
    
    /* Spacing */
    margin: 1rem;
    padding: 1rem;
    
    /* Styling */
    background: #fff;
    border: 1px solid #ddd;
    color: #333;
}

/* Mobile first - add media queries at bottom */
@media (max-width: 768px) {
    .element {
        flex-direction: row;
    }
}
```

### File Naming
```
Controllers/          → PascalCase: UserController.php
Models/              → PascalCase: Product.php
helpers.php          → lowercase: helpers.php
main.js              → lowercase: main.js
style.css            → lowercase: style.css
main.tpl             → lowercase: main.tpl
```

---

## 📝 Documentation

### PHPDoc Comments
```php
/**
 * Validates user input for product purchase.
 *
 * @param array $data User input data
 * @return bool True if valid, false otherwise
 * @throws InvalidArgumentException If required fields missing
 */
public function validatePurchase($data) {
    // Code here
}
```

### JSDoc Comments
```javascript
/**
 * Adds product to shopping cart via AJAX.
 * @param {number} productId - The product ID
 * @param {number} quantity - Quantity to add (default: 1)
 * @returns {Promise<Object>} Response from server
 */
async function addToCart(productId, quantity = 1) {
    // Code here
}
```

### Update Documentation Files
- Update `docs/API.md` for new endpoints
- Update `docs/DATABASE.md` for schema changes
- Update `README.md` for installation/setup changes
- Update `CHANGELOG.md` with version history

---

## 🔒 Security Guidelines

### DO NOT Commit
- `.env` files with credentials
- Database passwords
- API keys
- Private configuration
- Secrets of any kind

### Use Environment Variables
```php
// Good
$dbPassword = $_ENV['DB_PASSWORD'];
$apiKey = $_ENV['STRIPE_API_KEY'];

// Bad
$dbPassword = 'mypassword123';
$apiKey = 'sk_live_abc123xyz';
```

### Validate & Sanitize Input
```php
// Always validate user input
$productId = (int)$_POST['product_id'];
$quantity = max(1, (int)$_POST['quantity']);

// Sanitize strings
$name = htmlspecialchars($_POST['name']);
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
```

### Use Parameterized Queries
```php
// Good - prevents SQL injection
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);

// Bad - SQL injection risk
$query = "SELECT * FROM products WHERE id = " . $_GET['id'];
```

---

## 🐛 Reporting Bugs

### Issue Template
When reporting bugs, include:
```markdown
## Description
Clear description of the bug

## Steps to Reproduce
1. Go to...
2. Click...
3. See error...

## Expected Behavior
What should happen

## Actual Behavior
What actually happens

## Screenshots
If applicable

## Environment
- PHP Version: 8.3
- Browser: Chrome 120
- OS: Windows 11

## Error Message
Full error message if available
```

---

## 🚀 Performance Considerations

### Database Queries
```php
// Minimize database calls
$products = $this->productModel->getAll(['with' => 'category']);

// Use eager loading, not lazy loading
// Good: fetch related data in one query
// Bad: N+1 queries (one for products, one for each category)
```

### Caching
```php
// Cache expensive operations
$products = cache()->remember('all-products', 3600, function() {
    return Product::all();
});
```

### Asset Optimization
- Minimize CSS/JS files
- Compress images
- Use CDN for static files
- Lazy load images

---

## 📞 Getting Help

- **Code Review Issues**: @mention the team in PR comments
- **Questions**: Slack channel #carSelling-dev
- **Urgent Issues**: Team lead emergency contact
- **Documentation**: Check docs/ folder or WIKI

---

## ✅ Contribution Checklist

Before submitting PR:
- [ ] Feature branch created from develop
- [ ] All changes committed with meaningful messages
- [ ] Code follows style guide
- [ ] Tests written and passing
- [ ] Documentation updated
- [ ] No merge conflicts
- [ ] PR description complete
- [ ] Related issues linked
- [ ] Reviewers assigned
- [ ] No sensitive data committed

---

## 🎓 Team Standards

### Response Times
- Code review: 24 hours
- Bug fixes: 48 hours
- Feature implementation: Per sprint planning

### Meeting Schedule
- Daily standup: 10 AM (15 min)
- Code review session: Wed 2 PM (1 hour)
- Sprint planning: Friday 3 PM (1 hour)

### Release Cycle
- Sprint duration: 2 weeks
- Release day: Every other Friday
- Hotfix priority: Within 24 hours

---

Thank you for contributing to CarSelling! 🎉

For questions, reach out to the team lead or check our documentation.
