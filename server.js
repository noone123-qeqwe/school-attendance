// Smart Classroom Attendance System - Node.js wrapper for Laravel (Free Tier)
// This script starts PHP's built-in server for Render free deployment

const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');

const PORT = process.env.PORT || 10000;

console.log('🚀 Starting Smart Classroom Attendance System (Free Tier)...');

// Setup SQLite database file
const dbPath = path.join(__dirname, 'database', 'database.sqlite');
if (!fs.existsSync(dbPath)) {
    console.log('📁 Creating SQLite database file...');
    if (!fs.existsSync(path.dirname(dbPath))) {
        fs.mkdirSync(path.dirname(dbPath), { recursive: true });
    }
    fs.writeFileSync(dbPath, '');
    console.log('✅ SQLite database file created');
}

// Ensure PHP dependencies are installed
console.log('📦 Installing PHP dependencies...');
const composerInstall = spawn('composer', [
    'install', 
    '--optimize-autoloader', 
    '--no-dev', 
    '--no-interaction'
], { stdio: 'inherit' });

composerInstall.on('close', (code) => {
    if (code === 0) {
        console.log('✅ PHP dependencies installed');
        
        const envContent = fs.existsSync('.env') ? fs.readFileSync('.env', 'utf8') : '';
        const hasAppKey = envContent.includes('APP_KEY=base64:') || process.env.APP_KEY;

        const onKeyReady = () => {
            
            // Create storage link
            console.log('🔗 Creating storage link...');
            const storageLink = spawn('php', ['artisan', 'storage:link'], { stdio: 'inherit' });
            
            storageLink.on('close', (linkCode) => {
                if (linkCode === 0) {
                    console.log('✅ Storage link created');
                } else {
                    console.log('⚠️ Storage link may already exist');
                }
                
                // Run migrations
                console.log('🗄️ Running database migrations...');
                const migrate = spawn('php', ['artisan', 'migrate', '--force'], { stdio: 'inherit' });
                
                migrate.on('close', (migrateCode) => {
                    if (migrateCode === 0) {
                        console.log('✅ Migrations completed');
                        
                        // Seed demo data
                        if (process.env.SEED_DEMO_DATA === 'true') {
                            console.log('🌱 Seeding demo data...');
                            const seedData = spawn('php', ['artisan', 'db:seed', '--force'], { stdio: 'inherit' });
                            
                            seedData.on('close', (seedCode) => {
                                if (seedCode === 0) {
                                    console.log('✅ Demo data seeded');
                                } else {
                                    console.log('⚠️ Demo data seeding failed, continuing...');
                                }
                                
                                startServer();
                            });
                        } else {
                            startServer();
                        }
                    } else {
                        console.log('⚠️ Migrations failed, continuing...');
                        startServer();
                    }
                });
            });
        };

        if (!hasAppKey) {
            console.log('🔑 Generating application key...');
            const keyGenerate = spawn('php', ['artisan', 'key:generate', '--force'], { stdio: 'inherit' });
            
            keyGenerate.on('close', (keyCode) => {
                if (keyCode === 0) {
                    console.log('✅ Application key generated');
                }
                onKeyReady();
            });
        } else {
            console.log('✅ Application key already exists, skipping generation.');
            onKeyReady();
        }
    } else {
        console.error('❌ Failed to install PHP dependencies');
        process.exit(1);
    }
});

function startServer() {
    // Start PHP server
    console.log(`🌐 Starting PHP server on port ${PORT}...`);
    console.log('📊 Demo system ready with SQLite database');
    console.log('🎯 Perfect for presentations and demos!');
    
    const phpServer = spawn('php', [
        'artisan', 'serve', 
        `--host=0.0.0.0`, 
        `--port=${PORT}`
    ], { stdio: 'inherit' });
    
    phpServer.on('close', (serverCode) => {
        console.log(`PHP server exited with code ${serverCode}`);
        process.exit(serverCode);
    });
    
    phpServer.on('error', (err) => {
        console.error('Failed to start PHP server:', err);
        process.exit(1);
    });
}

// Handle process termination
process.on('SIGINT', () => {
    console.log('🛑 Shutting down server...');
    process.exit(0);
});

process.on('SIGTERM', () => {
    console.log('🛑 Server terminated');
    process.exit(0);
});