# Terraform IaC Modules for AWS Banking Infrastructure

This document contains 100 comprehensive questions covering Terraform Infrastructure as Code modules for AWS banking infrastructure, including VPC design, RDS encryption, and S3 configurations.

## Questions 1-20: VPC and Network Architecture

### Question 1
**Q:** Design a Terraform module for a banking VPC with 3 availability zones, private subnets for applications, and database subnets with proper CIDR allocation.

**A:** A complete VPC module should include vpc, subnets across multiple AZs, route tables, NAT gateways, and internet gateway. Use cidrsubnet() function for automatic CIDR calculation. Enable DNS hostnames and support. Tag all resources appropriately for compliance (PCI-DSS). Include flow logs for security monitoring.

### Question 2
**Q:** How do you implement VPC Flow Logs with CloudWatch integration using Terraform?

**A:** Create aws_flow_log resource with IAM role for CloudWatch Logs. Configure CloudWatch log group with retention policy (90 days for compliance). Use KMS encryption for logs. Create IAM policy allowing flow logs to write to CloudWatch. Enable for all traffic types (ACCEPT, REJECT, ALL).

### Question 3
**Q:** Implement high-availability NAT Gateways across multiple availability zones in Terraform.

**A:** Create one NAT Gateway per AZ for high availability. Allocate Elastic IPs for each NAT Gateway. Create separate route tables for each private subnet pointing to respective NAT Gateway. This ensures no single point of failure and reduces cross-AZ data transfer costs.

### Question 4
**Q:** Configure VPC endpoints for S3 and DynamoDB to avoid internet gateway traffic.

**A:** Use aws_vpc_endpoint resource with type Gateway for S3 and DynamoDB. Associate with route tables. Configure endpoint policies to restrict access. This keeps traffic within AWS network, reducing costs and improving security. No additional charges for gateway endpoints.

### Question 5
**Q:** Set up interface VPC endpoints for AWS services (SSM, EC2, ECR) with private DNS.

**A:** Create aws_vpc_endpoint with type Interface. Enable private_dns_enabled. Attach security group allowing HTTPS (443) from VPC CIDR. Deploy in multiple subnets for HA. Endpoints charged hourly plus data processing.

### Question 6
**Q:** Design network ACLs for defense in depth with explicit deny rules.

**A:** Create aws_network_acl resources with numbered rules. Lower numbers = higher priority. Add explicit deny rules (10-90) for known malicious IPs. Allow VPC traffic (100-110). Allow ephemeral ports (1024-65535) for return traffic. NACLs are stateless, so configure both ingress and egress.

### Question 7
**Q:** Implement Transit Gateway for multi-account VPC connectivity.

**A:** Create aws_ec2_transit_gateway with dns_support enabled. Disable default route table association for better control. Create separate route tables for prod/non-prod. Use RAM (Resource Access Manager) to share across accounts. Configure route table associations and propagations.

### Question 8
**Q:** Configure VPC peering with automatic route table updates using data sources.

**A:** Create aws_vpc_peering_connection from requester side. Use aws_vpc_peering_connection_accepter in accepter account. Query route tables using data sources with filters. Create routes dynamically using count or for_each. Handle cross-region and cross-account scenarios.

### Question 9
**Q:** Create security groups with dynamic rules using for_each for multiple ports.

**A:** Define locals with port configurations. Use dynamic blocks with for_each to create ingress rules. Reference security groups by ID for cross-group rules. Use description for each rule for documentation. Avoid circular dependencies.

### Question 10
**Q:** Implement AWS PrivateLink for secure service-to-service communication.

**A:** Create Network Load Balancer for service provider. Create aws_vpc_endpoint_service with NLB ARN. Consumer creates aws_vpc_endpoint pointing to service name. Approval required for additional security. Configure security groups and DNS.

### Question 11
**Q:** How do you implement VPC IPAM (IP Address Management) with Terraform?

**A:** Use aws_vpc_ipam resource for centralized IP management. Create pools with aws_vpc_ipam_pool. Allocate CIDRs with aws_vpc_ipam_pool_cidr. VPCs can request CIDRs from pools. Enables organization-wide IP planning and prevents conflicts.

### Question 12
**Q:** Configure AWS Network Firewall for VPC traffic inspection.

**A:** Create aws_networkfirewall_firewall with subnets. Define aws_networkfirewall_rule_group with rules. Create aws_networkfirewall_firewall_policy. Update route tables to direct traffic through firewall endpoints. Supports stateful and stateless rules.

### Question 13
**Q:** Set up VPC traffic mirroring for security analysis.

**A:** Create aws_ec2_traffic_mirror_target (NLB or ENI). Define aws_ec2_traffic_mirror_filter with rules. Create aws_ec2_traffic_mirror_session on source ENI. Mirror traffic to security appliance or monitoring tool. Useful for IDS/IPS systems.

### Question 14
**Q:** Implement IPv6 support in VPC with dual-stack configuration.

**A:** Associate IPv6 CIDR block with VPC using aws_vpc_ipv6_cidr_block_association. Assign IPv6 CIDRs to subnets. Update route tables for IPv6 routes (::/0). Configure security groups and NACLs for IPv6. Enable auto-assign IPv6 addresses.

### Question 15
**Q:** Design AWS Direct Connect integration with VPC using Terraform.

**A:** Create aws_dx_connection for physical connection. Use aws_dx_virtual_interface for BGP peering. Create aws_vpn_gateway and attach to VPC. Configure aws_dx_gateway for multi-VPC connectivity. Set up BGP ASNs and route propagation.

### Question 16
**Q:** Configure VPN connections with redundant tunnels for on-premises connectivity.

**A:** Create aws_customer_gateway for on-premises endpoint. Create aws_vpn_gateway attached to VPC. Configure aws_vpn_connection with two tunnels. Enable route propagation. Configure static or dynamic (BGP) routing. Monitor tunnel status with CloudWatch.

### Question 17
**Q:** Implement AWS Resource Access Manager (RAM) for subnet sharing.

**A:** Create aws_ram_resource_share. Use aws_ram_resource_association for subnets. Share with specific accounts using aws_ram_principal_association. Enables centralized VPC with shared subnets across accounts. Participants can launch resources in shared subnets.

### Question 18
**Q:** Set up VPC Reachability Analyzer for network troubleshooting.

**A:** Use aws_ec2_network_insights_path between source and destination. Run aws_ec2_network_insights_analysis. Analyzes configuration (not live traffic). Identifies blockers like security groups, NACLs, routes. Useful for validating network paths.

### Question 19
**Q:** Configure AWS Client VPN for remote user access.

**A:** Create aws_ec2_client_vpn_endpoint with authentication (certificates or Active Directory). Define aws_ec2_client_vpn_network_association with subnets. Create aws_ec2_client_vpn_authorization_rule. Configure split-tunnel or full-tunnel. Integrate with CloudWatch for logging.

### Question 20
**Q:** Implement VPC prefix lists for managing CIDR blocks centrally.

**A:** Create aws_ec2_managed_prefix_list with entries. Reference in security groups and route tables. Enables centralized CIDR management. Update prefix list to automatically update all references. Supports up to 1000 entries per list.

## Questions 21-40: RDS with Encryption at Rest

### Question 21
**Q:** Create RDS Aurora MySQL cluster with KMS encryption and IAM authentication.

**A:** Create aws_kms_key with rotation enabled. Create aws_rds_cluster with storage_encrypted=true and kms_key_id. Enable iam_database_authentication_enabled. Configure backup retention (30 days for banking). Enable CloudWatch log exports. Use aws_rds_cluster_instance for nodes.

### Question 22
**Q:** Implement automated RDS snapshots with cross-region replication.

**A:** Configure backup_retention_period on cluster. Use aws_db_instance_automated_backups_replication for cross-region. Create Lambda function triggered by snapshot events. Copy snapshots to DR region with aws_db_snapshot_copy. Encrypt with region-specific KMS key.

### Question 23
**Q:** Configure RDS Proxy for connection pooling with Secrets Manager.

**A:** Store credentials in aws_secretsmanager_secret. Create aws_db_proxy with auth using secret ARN. Enable require_tls. Configure connection_pool_config with max_connections_percent. Use aws_db_proxy_target for RDS cluster. Enable IAM authentication.

### Question 24
**Q:** Set up RDS read replicas across regions with automatic failover.

**A:** Create aws_rds_global_cluster. Primary cluster in aws_rds_cluster with global_cluster_identifier. Secondary cluster in different region. Use Route53 health checks for monitoring. Configure aws_route53_record with failover routing policy.

### Question 25
**Q:** Implement RDS Performance Insights with enhanced monitoring.

**A:** Enable performance_insights_enabled on instances. Specify performance_insights_kms_key_id. Set monitoring_interval (60 seconds recommended). Create aws_iam_role for enhanced monitoring. Attach AmazonRDSEnhancedMonitoringRole policy. View in CloudWatch and RDS console.

### Question 26
**Q:** Configure RDS parameter groups for optimal banking application performance.

**A:** Create aws_rds_cluster_parameter_group with family. Set parameters: slow_query_log, long_query_time, max_connections, innodb_buffer_pool_size. Enable require_secure_transport. Configure character_set_server=utf8mb4. Apply to cluster with parameter_group_name.

### Question 27
**Q:** Set up RDS event subscriptions for operational alerts.

**A:** Create aws_db_event_subscription for cluster/instance events. Specify event_categories (failure, failover, notification). Set sns_topic_arn for notifications. Subscribe to critical events like backup failure, configuration change, low storage.

### Question 28
**Q:** Implement RDS Blue/Green deployments using Terraform.

**A:** Not directly supported in Terraform. Workaround: Create new cluster with different identifier. Use AWS CLI/SDK for blue/green deployment via aws_rds_blue_green_deployment. Update application connection strings. Switch traffic using RDS Proxy or DNS update.

### Question 29
**Q:** Configure RDS custom engine parameters for security compliance.

**A:** Create aws_db_parameter_group. Set security parameters: ssl_ca, ssl_cert, ssl_key for TLS. Enable log_statement for audit. Set password_encryption. Configure authentication_timeout. Set connection limits per user.

### Question 30
**Q:** Set up RDS Multi-AZ with automatic failover configuration.

**A:** Set multi_az=true on aws_db_instance or aws_rds_cluster. Aurora is Multi-AZ by default with cluster spanning AZs. Automatic failover in 1-2 minutes. Use cluster endpoint for writes. Monitor with CloudWatch metrics. Test failover using AWS console reboot with failover.

### Question 31
**Q:** Implement RDS storage auto-scaling for dynamic workloads.

**A:** Set max_allocated_storage higher than allocated_storage. RDS automatically scales when free space < 10%. Scales in 10% increments or 10GB. No downtime. Monitor with CloudWatch metric FreeStorageSpace. Not available for Aurora (auto-scales automatically).

### Question 32
**Q:** Configure RDS option groups for advanced features (TDE, auditing).

**A:** Create aws_db_option_group with engine and version. Add options like MARIADB_AUDIT_PLUGIN or SQLSERVER_TDE. Configure option settings. Attach to instance with option_group_name. Some options require instance reboot.

### Question 33
**Q:** Set up RDS Database Activity Streams for real-time auditing.

**A:** Enable activity_stream_mode on aws_rds_cluster (async or sync). Specify activity_stream_kms_key_id. Streams to Kinesis Data Streams. Near real-time database activity. Immutable audit log for compliance. Monitor in Kinesis.

### Question 34
**Q:** Implement RDS Secrets Manager rotation for database credentials.

**A:** Create aws_secretsmanager_secret_rotation with automatic_rotation. Use Lambda rotation function (AWS provides templates). Set rotation_rules with schedule. RDS Proxy automatically uses rotated credentials. No application downtime during rotation.

### Question 35
**Q:** Configure RDS maintenance windows and backup windows properly.

**A:** Set preferred_maintenance_window (format: ddd:hh:mm-ddd:hh:mm). Set preferred_backup_window (non-overlapping). Choose low-traffic times. Maintenance includes OS patches, engine upgrades. Backup window for automated backups. Both UTC timezone.

### Question 36
**Q:** Set up RDS deletion protection and final snapshot policies.

**A:** Set deletion_protection=true on cluster/instance. Configure skip_final_snapshot=false. Set final_snapshot_identifier with timestamp. Use lifecycle prevent_destroy for Terraform safety. Require manual steps to delete production databases.

### Question 37
**Q:** Implement RDS data encryption in transit using SSL/TLS.

**A:** Download RDS CA certificate. Configure application to use SSL. Set parameter require_secure_transport=ON in parameter group. Use rds.force_ssl=1 for MySQL. Verify with status variable Ssl_cipher. Reject non-SSL connections.

### Question 38
**Q:** Configure RDS storage encryption for existing unencrypted databases.

**A:** Cannot encrypt in-place. Create encrypted snapshot from unencrypted. Restore snapshot to new encrypted instance with kms_key_id. Update application endpoints. Delete old instance. Requires downtime or blue/green deployment approach.

### Question 39
**Q:** Set up RDS custom VPC configuration with private subnets only.

**A:** Create aws_db_subnet_group with private subnets. Set publicly_accessible=false. Use security group allowing only application tier access. Route internet traffic through NAT Gateway. Access via bastion host or VPN for management.

### Question 40
**Q:** Implement RDS cost optimization with Reserved Instances.

**A:** Analyze usage patterns. Purchase Reserved Instances via AWS console (not Terraform managed). Choose 1 or 3 year term. Select payment option (all/partial/no upfront). Automatically applies to matching instances. Up to 69% savings vs on-demand.

## Questions 41-60: S3 Bucket with Versioning and Lifecycle Policies

### Question 41
**Q:** Create S3 bucket with versioning, encryption, and lifecycle policies for banking documents.

**A:** Create aws_s3_bucket. Enable aws_s3_bucket_versioning. Configure aws_s3_bucket_server_side_encryption_configuration with KMS. Set aws_s3_bucket_lifecycle_configuration with transitions to IA, Glacier, Deep Archive. Block public access. Enable logging.

### Question 42
**Q:** Implement S3 replication across regions with encryption and RTC.

**A:** Enable versioning on source and destination. Create aws_iam_role for replication. Configure aws_s3_bucket_replication_configuration with rule. Enable replication_time control for 15-minute SLA. Use different KMS keys per region. Set priority for multiple rules.

### Question 43
**Q:** Configure S3 bucket policies for least privilege access with encryption enforcement.

**A:** Use aws_s3_bucket_policy. Deny PutObject without encryption header. Deny requests without SecureTransport. Allow specific IAM roles/users. Use conditions for IP restrictions. Deny non-HTTPS requests. Follow principle of least privilege.

### Question 44
**Q:** Set up S3 object lock for WORM (Write Once Read Many) compliance.

**A:** Enable object_lock_enabled on bucket creation (cannot add later). Configure aws_s3_bucket_object_lock_configuration with retention mode (COMPLIANCE or GOVERNANCE). Set retention period days/years. Prevents deletion until retention expires. For SEC 17a-4 compliance.

### Question 45
**Q:** Implement S3 Intelligent-Tiering for automatic cost optimization.

**A:** Configure lifecycle rule with transition to INTELLIGENT_TIERING storage class. Automatically moves objects between access tiers. Archive access tier after 90 days. Deep archive after 180 days. No retrieval fees. Small monthly monitoring fee.

### Question 46
**Q:** Configure S3 event notifications to trigger Lambda for processing.

**A:** Create aws_s3_bucket_notification with lambda_function configuration. Specify events (s3:ObjectCreated:*). Set filter_prefix/filter_suffix. Create aws_lambda_permission for S3 to invoke. Process uploads, generate thumbnails, scan for malware.

### Question 47
**Q:** Set up S3 access logging for security auditing.

**A:** Create separate logging bucket with aws_s3_bucket_logging. Grant log-delivery-write ACL. Set target_prefix for organization. Logs contain requester, operation, response. Integrate with Athena for analysis. Retain logs per compliance requirements.

### Question 48
**Q:** Implement S3 bucket inventory for compliance reporting.

**A:** Configure aws_s3_bucket_inventory with schedule (daily/weekly). Output to destination bucket in CSV/ORC/Parquet. Include encryption status, storage class, replication status. Use for auditing, cost analysis. Query with Athena.

### Question 49
**Q:** Configure S3 Transfer Acceleration for faster uploads from remote locations.

**A:** Enable aws_s3_bucket_accelerate_configuration. Use accelerated endpoint format. Routes through CloudFront edge locations. Best for long-distance transfers. Additional cost per GB. Test with speed comparison tool.

### Question 50
**Q:** Set up S3 presigned URLs with expiration for temporary access.

**A:** Generate presigned URLs using AWS SDK (not Terraform). Set expiration time (max 7 days). Include content-type, ACL constraints. User-specific access without credentials. Useful for upload/download workflows. Revoke by rotating IAM credentials.

### Question 51
**Q:** Implement S3 multipart upload optimization for large files.

**A:** Configure lifecycle rule with abort_incomplete_multipart_upload. Set days_after_initiation (7 days). Use multipart for files >100MB. AWS SDK handles automatically. Improves performance and reliability. Clean up incomplete uploads to save costs.

### Question 52
**Q:** Configure S3 requester pays for data transfer cost shifting.

**A:** Set aws_s3_bucket_request_payment_configuration to Requester. Requesters pay for downloads and requests. Bucket owner pays storage. Require authenticated requests. Useful for large datasets shared with consumers.

### Question 53
**Q:** Set up S3 batch operations for bulk object processing.

**A:** Create inventory or CSV manifest. Use aws_s3control_job for operations (copy, tag, restore). Specify IAM role. Operations include initiate restore, replace tags, invoke Lambda. Track completion. Generate completion report.

### Question 54
**Q:** Implement S3 SELECT for efficient data retrieval.

**A:** Not directly in Terraform. Use AWS SDK S3 SelectObjectContent API. Query with SQL expressions. Supports CSV, JSON, Parquet. Retrieve subset of data. Up to 400% faster and 80% cheaper than full object retrieval.

### Question 55
**Q:** Configure S3 Storage Class Analysis for optimization recommendations.

**A:** Create aws_s3_bucket_analytics_configuration. Specify filter for prefix/tags. Exports daily CSV to destination bucket. Analyzes access patterns. Recommends transition to IA. Use to optimize lifecycle policies.

### Question 56
**Q:** Set up S3 Cross-Region Replication with S3 RTC for compliance.

**A:** Enable versioning on both buckets. Create replication configuration with metrics and replication_time_control. 99.99% objects replicate in 15 minutes. Monitor with CloudWatch metrics. Use for disaster recovery and compliance.

### Question 57
**Q:** Implement S3 bucket keys to reduce KMS costs.

**A:** Set bucket_key_enabled=true in encryption configuration. Reduces KMS requests by 99%. Uses bucket-level key to generate data keys. Lower KMS costs for high-throughput buckets. No impact on security.

### Question 58
**Q:** Configure S3 Glacier vault lock for compliance archives.

**A:** Create aws_glacier_vault. Set aws_glacier_vault_lock with policy. Once locked, policy immutable. Enforce retention periods. Prevent deletion. Compliance mode for SEC 17a-4(f), CFTC, FINRA.

### Question 59
**Q:** Set up S3 access points for simplified access management.

**A:** Create aws_s3_access_point with vpc_configuration. Define per-access-point policies. Simplify bucket policy. Delegate access control. Unique hostname per access point. Restrict to specific VPC.

### Question 60
**Q:** Implement S3 Object Lambda for data transformation on retrieval.

**A:** Create aws_s3_object_lambda_access_point. Configure supporting access point. Specify Lambda function for transformation. Process objects during retrieval. Use cases: redact PII, resize images, convert formats. No permanent copies.

## Questions 61-80: Advanced Terraform Patterns

### Question 61
**Q:** Implement Terraform workspaces for multi-environment management.

**A:** Use terraform workspace commands. Create workspaces for dev, staging, prod. Use terraform.workspace variable in configurations. Separate state files per workspace. Manage with same code. Switch with terraform workspace select.

### Question 62
**Q:** Design Terraform modules with input validation and preconditions.

**A:** Use variable validation blocks with condition and error_message. Validate CIDR blocks, instance types, regions. Use lifecycle precondition in resources. Check dependencies before apply. Fail fast with clear error messages.

### Question 63
**Q:** Implement Terraform remote state with state locking using S3 and DynamoDB.

**A:** Configure backend "s3" with bucket and key. Set dynamodb_table for locking. Enable encryption and versioning. Use IAM role for access. Prevents concurrent modifications. Critical for team collaboration.

### Question 64
**Q:** Use Terraform data sources to reference existing infrastructure.

**A:** Use data blocks for aws_vpc, aws_subnet, aws_ami. Query by tags, names, or IDs. Reference with data.<type>.<name>. Useful for importing existing resources. Reduces hardcoding. Enables modular design.

### Question 65
**Q:** Implement Terraform provisioners for post-creation configuration.

**A:** Use local-exec for local commands. Use remote-exec for SSH commands. Use file provisioner for uploads. Connection block for SSH details. Provisioners are last resort. Prefer cloud-init or user_data.

### Question 66
**Q:** Design Terraform module composition with nested modules.

**A:** Create root module calling child modules. Pass outputs as inputs. Use module.module_name.output_name. Version modules with source and version. Enable reusability. Maintain DRY principle.

### Question 67
**Q:** Implement Terraform count and for_each for dynamic resource creation.

**A:** Use count for homogeneous resources (count.index). Use for_each for heterogeneous (each.key, each.value). for_each with maps or sets. More flexible than count. Avoids index shifting on deletion.

### Question 68
**Q:** Use Terraform locals for computed values and DRY code.

**A:** Define locals block with computed expressions. Reference with local.name. Combine multiple variables. Format strings. Reduce duplication. Improve readability. Cannot be overridden like variables.

### Question 69
**Q:** Implement Terraform dynamic blocks for complex nested structures.

**A:** Use dynamic block inside resource. for_each over collection. Access with dynamic_block_name.value. Useful for ingress rules, tags. Reduces repetition. Makes code data-driven.

### Question 70
**Q:** Design Terraform provider configuration with aliases for multi-region.

**A:** Configure provider with alias. Reference with provider = provider_alias. Deploy to multiple regions. Cross-region replication. Multi-region architectures. Disaster recovery setups.

### Question 71
**Q:** Implement Terraform depends_on for explicit dependencies.

**A:** Use depends_on meta-argument. Specify list of resources/modules. Override automatic dependency detection. Useful for non-obvious dependencies. Ensure creation order. Handle API eventual consistency.

### Question 72
**Q:** Use Terraform lifecycle meta-arguments for resource management.

**A:** Set create_before_destroy for zero-downtime updates. Use prevent_destroy for critical resources. Set ignore_changes for externally managed attributes. Replace_triggered_by for forced replacement.

### Question 73
**Q:** Implement Terraform null_resource for triggers and provisioners.

**A:** Create null_resource with triggers map. Execute provisioners when triggers change. Useful for orchestration. Run scripts conditionally. Workaround for complex logic.

### Question 74
**Q:** Design Terraform outputs for module interfaces and state queries.

**A:** Define output blocks with value. Add description and sensitive flag. Export important attributes. Enable module composition. Query with terraform output. Use in other configurations.

### Question 75
**Q:** Implement Terraform random provider for dynamic value generation.

**A:** Use random_id, random_string, random_password. Generate unique names. Create secure passwords. Set keepers for recreation triggers. Stored in state. Consistent across applies.

### Question 76
**Q:** Use Terraform templatefile function for dynamic file rendering.

**A:** Use templatefile(path, vars) function. Pass variables to templates. Generate user_data scripts. Render config files. Support conditionals and loops. Cleaner than heredoc.

### Question 77
**Q:** Implement Terraform moved blocks for resource refactoring.

**A:** Use moved block with from and to addresses. Refactor without destroying. Rename resources safely. Reorganize module structure. Update state automatically. Available Terraform 1.1+.

### Question 78
**Q:** Design Terraform import workflow for existing infrastructure.

**A:** Write resource configuration. Run terraform import. Generate configuration from state. Use tools like terraformer. Incremental migration. Document manual changes.

### Question 79
**Q:** Implement Terraform sentinel policies for governance.

**A:** Write sentinel policies (Terraform Cloud/Enterprise). Enforce security rules. Restrict instance types. Require tags. Validate configurations. Advisory, soft-mandatory, hard-mandatory levels.

### Question 80
**Q:** Use Terraform cloud integration with VCS workflows.

**A:** Connect to GitHub/GitLab. Trigger on commits. Automated plans. Approval workflows. Collaboration features. Remote execution. State management. Cost estimation.

## Questions 81-100: Security and Compliance

### Question 81
**Q:** Implement AWS Config rules with Terraform for compliance monitoring.

**A:** Create aws_config_configuration_recorder and aws_config_delivery_channel. Define aws_config_config_rule for each compliance check. Use managed rules (encrypted-volumes, required-tags). Custom rules with Lambda. Remediate with Systems Manager Automation.

### Question 82
**Q:** Configure AWS GuardDuty for threat detection with Terraform.

**A:** Create aws_guardduty_detector with enable=true. Set aws_guardduty_member for multi-account. Configure aws_guardduty_filter for custom alerts. Integrate findings with Security Hub. Export to S3 with aws_guardduty_publishing_destination.

### Question 83
**Q:** Set up AWS Security Hub for centralized security findings.

**A:** Enable aws_securityhub_account. Subscribe to standards (AWS Foundational Security Best Practices, CIS). Configure aws_securityhub_insight for custom views. Aggregate from aws_securityhub_finding_aggregator. Automate responses with EventBridge.

### Question 84
**Q:** Implement AWS Secrets Manager rotation with Terraform.

**A:** Create aws_secretsmanager_secret. Configure aws_secretsmanager_secret_rotation with Lambda. Set rotation_rules schedule. Use AWS-provided rotation templates. Automatic credential rotation. No application changes with RDS Proxy.

### Question 85
**Q:** Configure AWS KMS key policies for fine-grained encryption control.

**A:** Create aws_kms_key with detailed policy. Grant root account full access. Allow service-specific permissions. Enable key administrators. Define key users. Use conditions for attribute-based access. Enable automatic rotation.

### Question 86
**Q:** Set up AWS CloudTrail for audit logging with integrity validation.

**A:** Create aws_cloudtrail with enable_log_file_validation. Configure aws_s3_bucket for logs with lifecycle. Set kms_key_id for encryption. Enable for all regions. Log to CloudWatch. Multi-region trail for organization.

### Question 87
**Q:** Implement AWS WAF with managed rule groups for web protection.

**A:** Create aws_wafv2_web_acl. Add managed_rule_group_statement (AWSManagedRulesCommonRuleSet). Configure rate limiting rules. Associate with aws_wafv2_web_acl_association to ALB/CloudFront. Log to aws_kinesis_firehose_delivery_stream.

### Question 88
**Q:** Configure AWS Shield Advanced for DDoS protection.

**A:** Subscribe to Shield Advanced (manual, not in Terraform). Use aws_shield_protection for resources. Configure aws_shield_protection_group. Access DDoS Response Team. Cost protection. Real-time metrics.

### Question 89
**Q:** Set up AWS Macie for sensitive data discovery.

**A:** Enable aws_macie2_account. Create aws_macie2_classification_job for S3 buckets. Configure scheduled or one-time runs. Identify PII, financial data. Review findings. Integrate with Security Hub and EventBridge.

### Question 90
**Q:** Implement AWS Systems Manager Parameter Store for configuration management.

**A:** Create aws_ssm_parameter with type String, StringList, or SecureString. Use kms_key_id for encryption. Organize with hierarchies. Version parameters automatically. Access from applications. Integrate with Secrets Manager.

### Question 91
**Q:** Configure AWS IAM password policy for user security.

**A:** Use aws_iam_account_password_policy. Set minimum_password_length (14+). Require symbols, numbers, uppercase, lowercase. Set max_password_age (90 days). Enable password_reuse_prevention. Enforce for all IAM users.

### Question 92
**Q:** Implement AWS Organizations SCPs for account-level controls.

**A:** Create aws_organizations_policy with type SERVICE_CONTROL_POLICY. Deny actions like leaving organization, disabling CloudTrail, deleting logs. Apply with aws_organizations_policy_attachment. Enforce at OU level. Inherited by member accounts.

### Question 93
**Q:** Set up AWS Control Tower for multi-account governance.

**A:** Control Tower primarily managed through console. Use Terraform to manage AWS Organizations structure. Create OUs with aws_organizations_organizational_unit. Apply guardrails via SCPs. Automate account provisioning with Service Catalog.

### Question 94
**Q:** Configure AWS Access Analyzer for unused access identification.

**A:** Create aws_accessanalyzer_analyzer with type ACCOUNT or ORGANIZATION. Analyzes resource policies. Identifies external access. Generates findings. Review and refine policies. Archive or resolve findings.

### Question 95
**Q:** Implement AWS Certificate Manager for SSL/TLS certificates.

**A:** Create aws_acm_certificate with domain_name. Use dns_validation for automation. Create aws_route53_record for validation. Wait for aws_acm_certificate_validation. Auto-renewal before expiration. Free for AWS services.

### Question 96
**Q:** Set up AWS Backup for centralized backup management.

**A:** Create aws_backup_vault with kms_key_arn. Define aws_backup_plan with rules and lifecycle. Create aws_backup_selection with IAM role and resources. Automated backups across services. Compliance reporting. Cross-region/account backup.

### Question 97
**Q:** Configure AWS CloudWatch Logs encryption and retention.

**A:** Create aws_cloudwatch_log_group with kms_key_id. Set retention_in_days (90, 180, 365). Use aws_cloudwatch_log_subscription_filter for streaming. Aggregate with Kinesis. Archive to S3 with Firehose.

### Question 98
**Q:** Implement AWS EventBridge for event-driven automation.

**A:** Create aws_cloudwatch_event_rule with event_pattern. Target with aws_cloudwatch_event_target (Lambda, SNS, SQS). React to AWS API calls, state changes. Build event-driven architectures. Replace CloudWatch Events.

### Question 99
**Q:** Set up AWS Cost Anomaly Detection for budget monitoring.

**A:** Create aws_ce_anomaly_monitor with monitor_type. Define aws_ce_anomaly_subscription for alerts. Set frequency and threshold_expression. ML-based detection. Receive notifications. Investigate with Cost Explorer.

### Question 100
**Q:** Configure AWS Trusted Advisor checks with automated responses.

**A:** Access via aws_support API (Business/Enterprise support). Use EventBridge to capture check results. Trigger Lambda for remediation. Monitor security, cost, performance, fault tolerance. Refresh checks programmatically. Dashboard in console.
