```markdown
# lunara-theme-blocks Development Patterns

> Auto-generated skill from repository analysis

## Overview
This skill provides guidance for contributing to the `lunara-theme-blocks` TypeScript codebase. It covers file naming, import/export conventions, commit message patterns, and testing strategies. While no specific frameworks or automated workflows are detected, this documentation will help maintain consistency and quality across the project.

## Coding Conventions

### File Naming
- All files use **kebab-case**.
  - Example: `theme-block.ts`, `color-picker.test.ts`

### Imports
- Use **relative imports** for all modules.
  - Example:
    ```typescript
    import { getTheme } from './theme-utils';
    ```

### Exports
- Use **named exports** exclusively.
  - Example:
    ```typescript
    export function createBlock() { ... }
    export const BLOCK_TYPES = [ ... ];
    ```

### Commit Messages
- Commit messages are **freeform** (no enforced prefixes).
- Average commit message length is ~27 characters.
  - Example:  
    ```
    add color picker block
    fix theme loading bug
    ```

## Workflows

### Adding a New Block
**Trigger:** When you want to introduce a new theme block to the project  
**Command:** `/add-block`

1. Create a new TypeScript file in kebab-case (e.g., `my-block.ts`).
2. Implement your block logic using named exports.
3. Add relative imports for any shared utilities.
4. Write a corresponding test file named `my-block.test.ts`.
5. Commit your changes with a clear, concise message.

### Refactoring Existing Code
**Trigger:** When improving or restructuring code for clarity or performance  
**Command:** `/refactor`

1. Identify the target file(s) and ensure all imports/exports follow conventions.
2. Update code, maintaining named exports and relative imports.
3. Run all tests to verify nothing breaks.
4. Commit with a descriptive message.

### Writing Tests
**Trigger:** When adding new features or fixing bugs  
**Command:** `/write-test`

1. Create or update a test file with the pattern `*.test.ts`.
2. Write tests covering new or changed functionality.
3. Ensure tests can be run with your chosen test runner.
4. Commit test changes.

## Testing Patterns

- Test files are named with the pattern `*.test.ts` and placed alongside or near the code they test.
- The specific testing framework is **unknown**; use standard TypeScript test runners (e.g., Jest, Mocha) as appropriate.
- Example test file:
  ```typescript
  import { createBlock } from './create-block';

  describe('createBlock', () => {
    it('should create a block with default values', () => {
      const block = createBlock();
      expect(block).toBeDefined();
    });
  });
  ```

## Commands

| Command      | Purpose                                     |
|--------------|---------------------------------------------|
| /add-block   | Scaffold and implement a new theme block    |
| /refactor    | Refactor existing code for improvement      |
| /write-test  | Add or update tests for features or fixes   |
```
