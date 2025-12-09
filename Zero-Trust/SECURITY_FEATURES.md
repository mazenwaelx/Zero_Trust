# ZeroTrustBank - Security Features Documentation

This document details all security features implemented in the application.

## 1. OTP Resend Limits
- Users can resend OTP **3 times only** (total 4 OTPs: 1 initial + 3 resends)
- Resend button available **30 seconds** after first OTP sent
- After 3 resends, **4th attempt blocks account for 5 minutes**
- Applies to all OTP purposes: signup, login, send_money, account_settings

## 2. Password Attempt Limits
- Users get **3 password attempts** only
- After 3 failed attempts, account is **blocked for 5 minutes**
- Warning shown after each failed attempt
- Applies to login attempts

## 3. Temporary Account Blocking (2 Minutes)
- Blocked accounts cannot login or request OTP for 2 minutes
- Blocking reasons:
  - Exceeded OTP resend limit (3 resends)
  - Failed password attempts (3 failures)
- Block expires automatically after 2 minutes
- All blocking events logged to activity log

## Database Changes Required

Run `SECURITY_UPDATE.sql` to:
1. Create `OTPResendTracking` table
2. Add `IsBlocked` column to Users table (if not exists)
3. Add performance indexes

## Files Created/Modified

### New Files:
- `resend_otp.php` - Handles OTP resend with limit tracking
- `SECURITY_UPDATE.sql` - Database schema updates
- `SECURITY_FEATURES.md` - This documentation

### Modified Files:
- `otp.php` - Added resend button with countdown timer
- `login_action.php` - Changed to permanent block after 2 password failures

## How It Works

### OTP Resend Flow:
1. User receives initial OTP
2. After 15 seconds, "Resend OTP" button becomes available
3. User can resend 3 times (total 4 OTPs)
4. On 4th resend attempt → **BLOCKED FOR 2 MINUTES**
5. After 2 minutes, block expires and user can try again

### Password Failure Flow:
1. User enters wrong password → Warning: "2 attempts remaining"
2. User enters wrong password again → Warning: "1 attempt remaining"
3. User enters wrong password 3rd time → **BLOCKED FOR 2 MINUTES**
4. After 2 minutes, block expires and attempts reset

### Activity Logging:
All security events are logged:
- `otp_resent` - OTP resend with count
- `otp_blocked_temp` - Temporary OTP block (2 minutes)
- `password_blocked_temp` - Temporary password block (2 minutes)
- `login_failed_password` - Failed password attempt
- `login_attempt_blocked` - Login attempt while blocked
