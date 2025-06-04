Phase 5: Deployment
Overview
In this phase, we deployed our Finance Tracker project to GitHub Pages using GitHub Actions for automated continuous deployment. This ensures the site is published every time changes are pushed to the main branch.

Step-by-Step Deployment
1. GitHub Pages Configuration
We navigated to the Settings > Pages section of our repository and selected GitHub Actions as the source for deployment.

📌 Figure 11: GitHub Pages configuration
![Figure 11 – Github Pages Configuration](./screenshots/Figure-11-Github-Pages-Config.png)
2. Added deploy.yml Workflow
A custom workflow file was created at .github/workflows/deploy.yml with the following configuration:
 Figure 12: deploy.yml workflow file
![Figure 12 – Deploy.yml file](./screenshots/Figure-12-Deploy-yml-file.png)
3. Workflow Execution and Fixes
Initially, the deployment failed due to permission issues (403 error). This was resolved by:

Ensuring the repository is public

Verifying the default GITHUB_TOKEN has write permissions

Re-running the workflow

📌 Figure 13: Successful GitHub Actions run
![Figure 13 – Successfull Github Actions Run](./screenshots/Figure-13-Successful-Github-Actions-run.png)

Final Deployment Link
After successful execution of the workflow, our Finance Tracker app was live at:

🔗 https://muzammil-static.github.io/finance-tracker/
