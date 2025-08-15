# MACTA Framework

**Modeling – Analysis – Customization – Training – Assessment (Metrics)**

A comprehensive process management framework developed by High Tech Talents (HTT) for strategic restructuring and process optimization.

## 🚀 Overview

The MACTA Framework provides a systematic approach to:
- **Process Modeling**: Visual representation and simulation of business processes
- **Statistical Analysis**: Data-driven insights and trend analysis
- **Customization**: Tailored job descriptions and client portals
- **Training Programs**: Real-world scenario-based training
- **Assessment & Metrics**: Performance tracking and continuous improvement

## 📋 Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Apache**: 2.4 or higher (with mod_rewrite enabled)
- **Laragon** (recommended for Windows development)

## 🛠 Installation

### Step 1: Download and Setup
1. Clone or download the project to your Laragon `www` directory
2. Navigate to `http://localhost/macta_framework`

### Step 2: Run Installation
1. Access `install.php` in your browser
2. Configure database connection:
   - Host: `localhost`
   - Database: `macta_framework` (will be created automatically)
   - Username: `root`
   - Password: (leave empty for default Laragon setup)

3. Create admin account with your preferred credentials

### Step 3: Verify Installation
- Installation complete screen should appear
- Access the main framework at `index.php`
- Login to admin panel at `admin/login.php`

## 📁 Project Structure

```
MACTA_Framework/
├── index.php                 # Main framework interface
├── install.php              # Installation script
├── config/                   # Configuration files
│   ├── database.php         # Database connection & schema
│   ├── config.php          # Auto-generated config
│   └── installed.lock      # Installation marker
├── modules/                 # MACTA modules
│   ├── M/                  # Modeling module
│   ├── A/                  # Analysis module
│   ├── C/                  # Customization module
│   ├── T/                  # Training module
│   └── A2/                 # Assessment (Metrics) module
├── admin/                  # Admin interface
├── shared/                 # Shared components
├── assets/                 # CSS, JS, images
└── uploads/               # File uploads
```

## 🎯 Module Overview

### M - Process Modeling
- Visual process builder with drag-and-drop functionality
- Process simulation and bottleneck analysis
- BPMN-compliant modeling tools
- Integration with real client data

### A - Statistical Analysis
- Advanced analytics and reporting
- Trend analysis and pattern recognition
- Custom dashboard creation
- Data-driven recommendations

### C - Customization
- Tailored job descriptions
- Client portal management
- Role-based access control
- Customizable performance metrics

### T - Training Program
- Real-world scenario-based training
- Interactive learning modules
- Progress tracking and assessment
- Certification management

### A2 - Assessment (Metrics)
- Real-time performance dashboards
- KPI tracking and monitoring
- Automated reporting
- Continuous improvement insights

## 🔧 Configuration

### Database Configuration
Edit `config/config.php` after installation:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'macta_framework');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### File Permissions
Ensure the following directories are writable:
- `config/`
- `uploads/`
- `uploads/documents/`
- `uploads/images/`

## 🚀 Development

### Adding New Features
1. Create new files in appropriate module directory (`modules/[MODULE]/`)
2. Use shared functions from `shared/functions.php`
3. Follow the established naming conventions
4. Include proper error handling and security measures

### Database Schema
The installation script automatically creates all necessary tables:
- `users` - User management
- `projects` - Project tracking
- `process_models` - Process modeling data
- `job_descriptions` - Customized job descriptions
- `training_programs` - Training content
- `metrics` - Performance metrics
- `customer_feedback` - Feedback collection

## 🔒 Security Features

- XSS protection through input sanitization
- SQL injection prevention with prepared statements
- File upload security with type validation
- Session management and authentication
- Directory access protection via `.htaccess`
- Secure password hashing

## 📱 Browser Compatibility

- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

## 🐛 Troubleshooting

### Common Issues

**Installation fails to connect to database:**
- Verify MySQL service is running in Laragon
- Check database credentials
- Ensure database name doesn't already exist

**Permission denied errors:**
- Check file permissions on `config/` and `uploads/` directories
- Ensure Apache has write access

**Module pages show 404 errors:**
- Verify `.htaccess` file is present
- Check that `mod_rewrite` is enabled in Apache

## 📞 Support

For support and questions:
- **Company**: High Tech Talents (HTT)
- **Framework**: MACTA Framework™
- **Version**: 1.0.0

## 📄 License

© 2025 High Tech Talents (HTT). MACTA Framework™ and related marks are trademarks of HTT.

## 🔄 Version History

### v1.0.0 (Current)
- Initial release with all 5 MACTA modules
- Complete installation and setup system
- Basic admin interface
- Database schema and security implementation

## 🚀 Roadmap

### v1.1.0 (Planned)
- Enhanced visual process builder
- Advanced analytics dashboard
- API integrations
- Mobile responsiveness improvements

### v1.2.0 (Planned)
- Real-time collaboration features
- Advanced reporting engine
- Third-party integrations
- Performance optimizations

## 🤝 Contributing

This is a proprietary framework developed by High Tech Talents. For feature requests or bug reports, please contact the development team.

## 📋 Development Notes

### Coding Standards
- Use PSR-4 autoloading standards
- Follow PHP coding conventions
- Use prepared statements for all database queries
- Implement proper error handling and logging
- Comment complex functions and algorithms

### Testing Environment
- Developed and tested on Laragon (Windows)
- Compatible with XAMPP and WAMP
- Tested on PHP 7.4+ and MySQL 5.7+

### Future Integrations
The framework is designed to integrate with "the Tool" project and other HTT development tools.