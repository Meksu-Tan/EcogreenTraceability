#!/bin/bash
# ================================================================
# Dev Agent - Shell Wrapper for AI-Assisted Development
# ================================================================
# Purpose: Automatically inject Layer 1 (Convention) + Layer 2 (Blueprint)
#          context into Claude Code sessions, ensuring consistent
#          architecture and sprint alignment without manual copy-paste.
#
# Usage:
#   ./dev-agent.sh "Task description"
#
# With sprint override:
#   SPRINT=3 ./dev-agent.sh "Implement Feature from sprint-03.md"
#
# Advanced usage with executor delegation:
#   EXECUTOR=opencode ./dev-agent.sh "Generate CRUD module via OpenCode"
#
# ================================================================

set -e  # Exit on error

# Configuration
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CLAUDE_MD="${PROJECT_ROOT}/.claude/CLAUDE.md"
ARCH_MD="${PROJECT_ROOT}/.docs/ARCHITECTURE.md"
SPRINT_NUM="${SPRINT:-1}"  # Default to sprint 1 if not specified
DESIGN_MD="${PROJECT_ROOT}/.design/DESIGN-SYSTEM.md"
COLLAB_MD="${PROJECT_ROOT}/COLLABORATION.md"

# Memory injection (optional, if .context exists)
CONTEXT_MEMORY=""
if [ -d "${PROJECT_ROOT}/.context" ]; then
    CONTEXT_MEM_FILE="${PROJECT_ROOT}/.context/MEMORY.md"
    if [ -f "${CONTEXT_MEM_FILE}" ]; then
        CONTEXT_MEMORY="=== MEMORY INDEX ===\n$(cat ${CONTEXT_MEM_FILE})\n\n"
    fi
fi

# Task validation
TASK="${1:-}"
if [ -z "$TASK" ]; then
    echo "❌ Error: No task specified"
    echo ""
    echo "Usage:"
    echo "  $0 \"Task description\""
    echo "  SPRINT=3 $0 \"Implement Feature from sprint-03.md\""
    echo "  EXECUTOR=opencode $0 \"Generate CRUD via executor\""
    echo ""
    echo "Examples:"
    echo "  $0 'Create Plant CRUD endpoint'"
    echo "  SPRINT=1A $0 'Migrate Plant data from reference system'"
    echo "  ./dev-agent.sh 'Review codebase for CLAUDE.md compliance'"
    exit 1
fi

echo "=========================================="
echo "🚀 Dev Agent - Context Injection Active"
echo "=========================================="
echo "Project: ${PROJECT_ROOT}"
echo "Target Sprint: ${SPRINT_NUM}"
echo "Executor: ${EXECUTOR:-Claude}"
echo ""

# Check critical files exist
MISSING_FILES=()

if [ ! -f "$CLAUDE_MD" ]; then
    MISSING_FILES+=("CLAUDE.md")
fi

if [ ! -f "$ARCH_MD" ]; then
    MISSING_FILES+=("ARCHITECTURE.md")
fi

SPRINT_MD="${PROJECT_ROOT}/.docs/sprints/sprint-${SPRINT_NUM}.md"
if [ ! -f "$SPRINT_MD" ]; then
    MISSING_FILES+=("sprint-${SPRINT_NUM}.md")
fi

if [ ${#MISSING_FILES[@]} -gt 0 ]; then
    echo "⚠️  Warning: Missing required files:"
    for file in "${MISSING_FILES[@]}"; do
        echo "   - $file"
    done
    echo ""
    echo "Please ensure these files exist before proceeding."

    # Continue anyway but warn (sometimes sprint files are phase-specific)
fi

# Read context files
echo "📖 Loading context files..."
LAYER1=$(cat "$CLAUDE_MD" 2>/dev/null || echo "# CLAUDE.md not found - using defaults")
LAYER1_ARCH=$(cat "$ARCH_MD" 2>/dev/null || echo "# ARCHITECTURE.md not found")
SPRINT_CONTENT=$(cat "$SPRINT_MD" 2>/dev/null | head -200 || echo "# Sprint ${SPRINT_NUM} details not available")
LAYER1_DESIGN=$(cat "$DESIGN_MD" 2>/dev/null | head -150 || echo "# Design system reference")
COLLAB_CONTENT=$(cat "$COLLAB_MD" 2>/dev/null | head -100 || echo "# Collaboration guide")

# Build complete context string
CONTEXT_INJECTION="
${CONTEXT_MEMORY}
=== LAYER 1: CONVENTION (CLAUDE.md) ===
$LAYER1

=== LAYER 1: ARCHITECTURE (ARCHITECTURE.md) ===
$LAYER1_ARCH

=== LAYER 1: DESIGN SYSTEM (DESIGN-SYSTEM.md) ===
$LAYER1_DESIGN

${COLLAB_CONTENT:0:5000}

=== LAYER 2: SPRINT BLUEPRINT (sprint-${SPRINT_NUM}.md) ===
$SPRINT_CONTENT

=== EXECUTOR MODE ===
Using Executor: ${EXECUTOR:-Claude Code}
Follow all patterns in COLLABORATION.md if delegating tasks.
"

echo "✅ Context loaded successfully"
echo ""
echo "=========================================="
echo "📋 TASK CONTEXT INJECTED"
echo "=========================================="
echo ""
echo "Task:"
echo "  \"$TASK\""
echo ""
echo "Active Files Referenced:"
echo "  ✓ .claude/CLAUDE.md"
echo "  ✓ .docs/ARCHITECTURE.md"
echo "  ✓ .docs/sprints/sprint-${SPRINT_NUM}.md"
echo "  ✓ .design/DESIGN-SYSTEM.md"
echo "  ✓ COLLABORATION.md"
echo ""
if [ -n "$CONTEXT_MEMORY" ]; then
    echo "  ✓ .context/MEMORY.md"
fi
echo ""

# Launch Claude with injected context
if command -v claude &> /dev/null; then
    echo "🚀 Launching Claude Code with context..."
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

    # Use heredoc to pass full context to Claude
    claude chat <<EOF
Context Injection Applied Successfully

You are operating in ${EXECUTOR:-Claude Code} mode for project EODS Refactoring.

The following context has been automatically loaded and is MANDATORY:

1. **Layer 1 - Convention Rules:** All coding standards, naming conventions, restrictions from CLAUDE.md
2. **Layer 1 - Architecture:** System patterns, modularity rules, API design from ARCHITECTURE.md
3. **Layer 1 - Design System:** Brand tokens, Vuetify theme, visual specs from DESIGN-SYSTEM.md
4. **Layer 2 - Sprint Blueprint:** Current sprint goals, features, acceptance criteria from sprint-${SPRINT_NUM}.md
5. **Collaboration Guide:** How to coordinate with other executors if needed from COLLABORATION.md

If ${EXECUTOR:-Claude} is not the primary executor, follow delegation patterns in COLLABORATION.md section on brief writing.

---

CURRENT TASK:
$TASK

---

IMPORTANT RULES:
- Read ALL provided context BEFORE implementing anything
- Confirm understanding of sprint goals and architectural constraints
- Follow C→S→R pattern strictly (Controller → Service → Repository → Model)
- Validate against CLAUDE.md restrictions (no hardcoded values, strict_types=1, etc.)
- If task exceeds single-executor scope, delegate per COLLABORATION.md patterns
- Always run tests before claiming completion
- Update CLAUDE.md Lessons Learned if new patterns discovered

CONFIRMATION REQUEST:
Please summarize:
1. Your understanding of the task within sprint context
2. Key architectural decisions you'll make
3. Potential blockers or questions
4. Expected timeline/complexity

Do NOT start implementation until I confirm your plan is aligned.

---
EOF

else
    echo "❌ Error: 'claude' command not found"
    echo ""
    echo "Please install Claude Code CLI:"
    echo "  npm install -g @anthropic-ai/claude-code"
    echo ""
    echo "Or manually paste this context into your Claude session:"
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "$CONTEXT_INJECTION"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

    echo ""
    echo "Then send this task to Claude:"
    echo "  \"$TASK\""
    exit 1
fi
