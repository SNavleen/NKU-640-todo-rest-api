#!/bin/bash

# AWS Cleanup Script for PHP TODO API
# This script removes all AWS resources created for the project

set -e

echo "=== AWS Cleanup Script ==="
echo "This will delete all AWS resources for the PHP TODO API project"
echo ""

# Configuration
KEY_NAME="php-todo-api-key"
SECURITY_GROUP_NAME="php-todo-api-sg"
INSTANCE_TAG_NAME="php-todo-api"

# Confirm deletion
read -p "Are you sure you want to delete all resources? (yes/no): " CONFIRM
if [ "$CONFIRM" != "yes" ]; then
    echo "Cleanup cancelled."
    exit 0
fi

echo ""
echo "Starting cleanup process..."

# Find and terminate EC2 instances
echo "Looking for EC2 instances with tag Name=$INSTANCE_TAG_NAME..."
INSTANCE_IDS=$(aws ec2 describe-instances \
    --filters "Name=tag:Name,Values=$INSTANCE_TAG_NAME" "Name=instance-state-name,Values=running,stopped,stopping,pending" \
    --query 'Reservations[*].Instances[*].InstanceId' \
    --output text)

if [ -n "$INSTANCE_IDS" ]; then
    echo "Found instances: $INSTANCE_IDS"
    echo "Terminating EC2 instances..."
    aws ec2 terminate-instances --instance-ids $INSTANCE_IDS

    echo "Waiting for instances to terminate..."
    aws ec2 wait instance-terminated --instance-ids $INSTANCE_IDS
    echo "Instances terminated successfully"
else
    echo "No running instances found"
fi

# Delete security group
echo ""
echo "Deleting security group: $SECURITY_GROUP_NAME..."
if aws ec2 describe-security-groups --group-names "$SECURITY_GROUP_NAME" &> /dev/null; then
    SG_ID=$(aws ec2 describe-security-groups --group-names "$SECURITY_GROUP_NAME" --query 'SecurityGroups[0].GroupId' --output text)

    # Wait a bit to ensure instances are fully terminated before deleting security group
    echo "Waiting 10 seconds for instance cleanup..."
    sleep 10

    aws ec2 delete-security-group --group-id "$SG_ID"
    echo "Security group deleted: $SG_ID"
else
    echo "Security group not found"
fi

# Delete key pair
echo ""
echo "Deleting key pair: $KEY_NAME..."
if aws ec2 describe-key-pairs --key-names "$KEY_NAME" &> /dev/null; then
    aws ec2 delete-key-pair --key-name "$KEY_NAME"
    echo "Key pair deleted from AWS"

    # Remove local .pem file if it exists
    if [ -f "php-todo-api-key.pem" ]; then
        rm -f php-todo-api-key.pem
        echo "Local .pem file removed"
    fi
else
    echo "Key pair not found in AWS"
fi

echo ""
echo "=== Cleanup Complete! ==="
echo "All AWS resources have been removed."
echo ""
echo "Summary of deleted resources:"
echo "  - EC2 Instances: ${INSTANCE_IDS:-None}"
echo "  - Security Group: $SECURITY_GROUP_NAME"
echo "  - Key Pair: $KEY_NAME"
echo ""
