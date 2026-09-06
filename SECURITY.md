# Security Policy

## Reporting a vulnerability

Please report suspected vulnerabilities privately to **majdbayer77@gmail.com**.

Do not open public issues containing credentials, access tokens, private keys, personal data, exploitable authorization details, or live infrastructure information before remediation.

Include the affected component, reproducible steps, impact, role/authorization context, and sanitized supporting evidence.

## Priority areas

High-priority reports include authentication/authorization bypass, role or ownership-scope failures, vendor/customer data exposure, unsafe uploads, order/checkout integrity issues, injection, secret leakage, real-time channel authorization issues, and production deployment vulnerabilities.

## Secrets and generated artifacts

Production secrets belong in environment configuration, GitHub Secrets, or provider secret stores and must not be committed.

Generated browser/audit artifacts are local outputs and should remain ignored. Any credential that has ever appeared in Git history must be rotated/revoked externally; deleting it from the current branch is insufficient.

## Supported code

Security remediation targets the current default branch and maintained deployment paths. Historical revisions are not supported production baselines.
