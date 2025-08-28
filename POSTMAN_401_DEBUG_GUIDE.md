<?php

echo "🔍 Postman 401 Error Debug Guide\n";
echo "=================================\n\n";

echo "If you're getting 401 errors in Postman but the system works with our test scripts,\n";
echo "here's how to debug your specific setup:\n\n";

echo "📋 CHECKLIST FOR POSTMAN 401 ERRORS:\n";
echo "=====================================\n\n";

echo "1. 🔑 Verify Your Token\n";
echo "-----------------------\n";
echo "• Make sure you're using the correct token\n";
echo "• Working token: 302|rHIMict2m9i8FJSZlTOHhgIrREgjya9C6fwihnCA662be60a\n";
echo "• Check for extra spaces or characters\n\n";

echo "2. 🔧 Check Authorization Header Format\n";
echo "----------------------------------------\n";
echo "✅ CORRECT format:\n";
echo "   Authorization: Bearer 302|rHIMict2m9i8FJSZlTOHhgIrREgjya9C6fwihnCA662be60a\n\n";
echo "❌ WRONG formats:\n";
echo "   Authorization: Bearer 302|rHIMict2m9i8FJSZlTOHhgIrREgjya9C6fwihnCA662be60a (extra space)\n";
echo "   Authorization: Bearer \"302|rHIMict2m9i8FJSZlTOHhgIrREgjya9C6fwihnCA662be60a\" (quotes)\n";
echo "   Authorization: 302|rHIMict2m9i8FJSZlTOHhgIrREgjya9C6fwihnCA662be60a (missing Bearer)\n\n";

echo "3. 🌐 Check Request URL\n";
echo "-----------------------\n";
echo "✅ CORRECT:\n";
echo "   http://127.0.0.1:8000/api/v1/withdrawal/send-verification-code\n\n";
echo "❌ WRONG:\n";
echo "   http://localhost:8000/api/v1/withdrawal/send-verification-code (different host)\n";
echo "   http://127.0.0.1:8000/withdrawal/send-verification-code (missing /api/v1)\n\n";

echo "4. 📝 Check Content-Type Header\n";
echo "-------------------------------\n";
echo "Make sure you have:\n";
echo "Content-Type: application/json\n\n";

echo "5. 🧪 Test Step by Step\n";
echo "-----------------------\n\n";

echo "Step 1: Test with a simple endpoint first\n";
echo "POST http://127.0.0.1:8000/api/v1/login\n";
echo "Body: {\"email\": \"admin@example.com\", \"password\": \"password123\"}\n\n";

echo "Step 2: Test auth-test endpoint\n";
echo "GET http://127.0.0.1:8000/api/v1/auth-test\n";
echo "Headers: Authorization: Bearer YOUR_TOKEN_HERE\n\n";

echo "Step 3: Test withdrawal endpoint\n";
echo "POST http://127.0.0.1:8000/api/v1/withdrawal/send-verification-code\n";
echo "Headers:\n";
echo "  Content-Type: application/json\n";
echo "  Authorization: Bearer YOUR_TOKEN_HERE\n";
echo "Body: {\"email\": \"admin@example.com\"}\n\n";

echo "6. 🔍 Check Laravel Logs While Testing\n";
echo "=======================================\n";
echo "Run this command in a separate terminal:\n";
echo "Get-Content storage/logs/laravel.log -Wait -Tail 5\n\n";

echo "This will show you real-time logs including:\n";
echo "• Request headers\n";
echo "• Bearer token received\n";
echo "• Authentication results\n";
echo "• Any errors\n\n";

echo "7. 🐛 Common Postman Issues\n";
echo "=============================\n\n";

echo "• Environment variables not set correctly\n";
echo "• Authorization helper not configured\n";
echo "• Wrong HTTP method (GET vs POST)\n";
echo "• Body format not set to JSON\n";
echo "• Extra spaces in headers\n";
echo "• Using wrong token from previous sessions\n\n";

echo "8. 🎯 Quick Test in Postman\n";
echo "===========================\n\n";

echo "1. Create new request\n";
echo "2. Set method to POST\n";
echo "3. URL: http://127.0.0.1:8000/api/v1/withdrawal/send-verification-code\n";
echo "4. Go to Headers tab:\n";
echo "   Content-Type: application/json\n";
echo "   Authorization: Bearer 302|rHIMict2m9i8FJSZlTOHhgIrREgjya9C6fwihnCA662be60a\n";
echo "5. Go to Body tab:\n";
echo "   Select 'raw' and 'JSON'\n";
echo "   Enter: {\"email\": \"admin@example.com\"}\n";
echo "6. Click Send\n\n";

echo "9. 📞 If Still Getting 401\n";
echo "===========================\n\n";

echo "Run this command and share the output:\n";
echo "Get-Content storage/logs/laravel.log | Select-String -Pattern \"EmailVerificationController\" | Select-Object -Last 5\n\n";

echo "This will show us exactly what your request looks like from the server side.\n\n";

echo "🚀 REMEMBER:\n";
echo "=============\n";
echo "The system IS working! Our test scripts prove it.\n";
echo "The 401 error is specific to your Postman setup.\n";
echo "Follow the checklist above and you'll get it working! 🎉\n";
