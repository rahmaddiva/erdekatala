#!/usr/bin/env python3
"""
Code Quality Checker untuk PHP/CodeIgniter 4
Melakukan analisis kualitas kode: security, style, best practices, CI4 patterns.
"""

import os
import re
import sys
import json
import argparse
from pathlib import Path
from typing import Dict, List, Tuple

# ===========================================================================
# Rule definitions
# ===========================================================================

SECURITY_RULES = [
    {
        'id': 'SEC-001',
        'name': 'Raw SQL query tanpa query builder',
        'pattern': r'\$this->db->query\s*\(\s*["\'].*\$',
        'severity': 'HIGH',
        'message': 'Query langsung dengan variabel PHP berpotensi SQL Injection. Gunakan parameter binding.',
    },
    {
        'id': 'SEC-002',
        'name': 'Output tanpa escape (echo variabel langsung)',
        'pattern': r'<\?=\s*\$(?!this|session|data|title|errors?|success|warning|info)[a-zA-Z_]+\s*\?>',
        'severity': 'MEDIUM',
        'message': 'Output variabel tanpa esc(). Gunakan <?= esc($var) ?> untuk mencegah XSS.',
    },
    {
        'id': 'SEC-003',
        'name': 'password_hash tidak digunakan (plain text password)',
        'pattern': r"['\"]password['\"]\s*=>\s*\$(?!.*password_hash)",
        'severity': 'HIGH',
        'message': 'Kemungkinan menyimpan password tanpa hashing. Gunakan password_hash().',
    },
    {
        'id': 'SEC-004',
        'name': 'eval() berbahaya',
        'pattern': r'\beval\s*\(',
        'severity': 'CRITICAL',
        'message': 'Penggunaan eval() sangat berbahaya dan harus dihindari.',
    },
    {
        'id': 'SEC-005',
        'name': 'var_dump / print_r tertinggal di production',
        'pattern': r'\b(var_dump|print_r|var_export)\s*\(',
        'severity': 'LOW',
        'message': 'Debug statement tertinggal. Hapus sebelum production.',
    },
    {
        'id': 'SEC-006',
        'name': 'Direct $_GET/$_POST/$_REQUEST tanpa CI4 helper',
        'pattern': r'\$_(GET|POST|REQUEST|SERVER|COOKIE)\s*\[',
        'severity': 'MEDIUM',
        'message': 'Gunakan $this->request->getGet()/getPost() bukan superglobal langsung di CI4.',
    },
]

CI4_RULES = [
    {
        'id': 'CI4-001',
        'name': 'Controller tidak extends BaseController',
        'pattern': r'class\s+\w+Controller\s+extends\s+(?!BaseController)\w+',
        'severity': 'LOW',
        'message': 'Controller sebaiknya extends BaseController untuk konsistensi.',
    },
    {
        'id': 'CI4-002',
        'name': 'Redirect tanpa return',
        'pattern': r'^(?!\s*return)\s+redirect\(\)',
        'severity': 'MEDIUM',
        'message': 'redirect() di CI4 harus di-return: return redirect()->to(...).',
    },
    {
        'id': 'CI4-003',
        'name': 'Model tidak mendefinisikan $allowedFields',
        'pattern': r'class\s+\w+Model\s+extends\s+Model',
        'severity': 'MEDIUM',
        'message': 'Model harus mendefinisikan $allowedFields untuk mencegah mass assignment.',
        'check_absence': r'\$allowedFields',
    },
    {
        'id': 'CI4-004',
        'name': 'die() atau exit() di controller/model',
        'pattern': r'\b(die|exit)\s*\(',
        'severity': 'MEDIUM',
        'message': 'Hindari die()/exit() di CI4. Gunakan throw Exception atau return response.',
    },
    {
        'id': 'CI4-005',
        'name': 'Session diakses langsung tanpa service',
        'pattern': r'\$_SESSION\s*\[',
        'severity': 'MEDIUM',
        'message': 'Gunakan CI4 session service: session()->get() bukan $_SESSION langsung.',
    },
]

QUALITY_RULES = [
    {
        'id': 'QUA-001',
        'name': 'Fungsi terlalu panjang (>80 baris)',
        'severity': 'LOW',
        'message': 'Fungsi melebihi 80 baris. Pertimbangkan untuk dipecah.',
        'special': 'long_function',
    },
    {
        'id': 'QUA-002',
        'name': 'TODO/FIXME/HACK comment',
        'pattern': r'//\s*(TODO|FIXME|HACK|XXX|BUG)\b',
        'severity': 'INFO',
        'message': 'Ditemukan technical debt comment.',
    },
    {
        'id': 'QUA-003',
        'name': 'Magic number (angka hardcoded)',
        'pattern': r'(?<!define\()(?<!const\s)(?<![\w\'"=>])\b([2-9][0-9]{2,}|[1-9][0-9]{3,})\b(?![\w\'".])',
        'severity': 'INFO',
        'message': 'Magic number ditemukan. Pertimbangkan menggunakan konstanta bernama.',
    },
    {
        'id': 'QUA-004',
        'name': 'Duplikasi kondisi if-else panjang',
        'pattern': r'if\s*\(.*role.*==.*\).*\{[\s\S]{0,200}elseif\s*\(.*role.*==.*\).*\{[\s\S]{0,200}elseif\s*\(.*role.*==.*\)',
        'severity': 'LOW',
        'message': 'Kondisi role check berulang. Pertimbangkan menggunakan strategy pattern atau middleware.',
    },
    {
        'id': 'QUA-005',
        'name': 'Catch exception kosong',
        'pattern': r'catch\s*\([^)]+\)\s*\{\s*\}',
        'severity': 'MEDIUM',
        'message': 'Catch block kosong menelan error. Tambahkan penanganan atau logging.',
    },
]

# ===========================================================================
# Helpers
# ===========================================================================

SEVERITY_ORDER = {'CRITICAL': 0, 'HIGH': 1, 'MEDIUM': 2, 'LOW': 3, 'INFO': 4}
SEVERITY_COLOR = {
    'CRITICAL': '\033[1;31m',
    'HIGH':     '\033[31m',
    'MEDIUM':   '\033[33m',
    'LOW':      '\033[36m',
    'INFO':     '\033[90m',
    'RESET':    '\033[0m',
}


def severity_icon(sev: str) -> str:
    return {'CRITICAL': '[!!]', 'HIGH': '[!] ', 'MEDIUM': '[~] ', 'LOW': '[-] ', 'INFO': '[i] '}.get(sev, '    ')


def collect_php_files(base: Path, exclude_dirs=None) -> List[Path]:
    exclude_dirs = exclude_dirs or {'vendor', 'node_modules', '.git', 'writable', 'public'}
    files = []
    for p in base.rglob('*.php'):
        if not any(part in exclude_dirs for part in p.parts):
            files.append(p)
    return sorted(files)


def count_function_lines(content: str) -> List[Tuple[str, int, int]]:
    """Return list of (func_name, start_line, line_count) for long functions."""
    results = []
    lines = content.splitlines()
    func_pattern = re.compile(r'^\s*(?:public|protected|private|static|\s)*function\s+(\w+)\s*\(')
    stack = []
    brace_count = 0
    in_func = False
    func_name = ''
    func_start = 0

    for i, line in enumerate(lines, 1):
        m = func_pattern.match(line)
        if m and not in_func:
            in_func = True
            func_name = m.group(1)
            func_start = i
            brace_count = line.count('{') - line.count('}')
        elif in_func:
            brace_count += line.count('{') - line.count('}')
            if brace_count <= 0:
                length = i - func_start + 1
                if length > 80:
                    results.append((func_name, func_start, length))
                in_func = False
                brace_count = 0
    return results


# ===========================================================================
# Core checker
# ===========================================================================

class CodeQualityChecker:
    def __init__(self, target_path: str, verbose: bool = False):
        self.target_path = Path(target_path)
        self.verbose = verbose
        self.findings: List[Dict] = []
        self.stats = {
            'files_scanned': 0,
            'lines_scanned': 0,
            'CRITICAL': 0, 'HIGH': 0, 'MEDIUM': 0, 'LOW': 0, 'INFO': 0,
        }

    def run(self) -> Dict:
        if not self.target_path.exists():
            print(f'Path tidak ditemukan: {self.target_path}')
            sys.exit(1)

        print(f'\n=== Code Quality Checker ===')
        print(f'Target : {self.target_path}')

        php_files = collect_php_files(self.target_path)
        print(f'File PHP: {len(php_files)} file ditemukan\n')

        for f in php_files:
            self._check_file(f)

        self._generate_report()
        return {'findings': self.findings, 'stats': self.stats}

    def _check_file(self, filepath: Path):
        try:
            content = filepath.read_text(encoding='utf-8', errors='ignore')
        except Exception:
            return

        lines = content.splitlines()
        self.stats['files_scanned'] += 1
        self.stats['lines_scanned'] += len(lines)
        rel = str(filepath.relative_to(self.target_path))

        all_rules = SECURITY_RULES + CI4_RULES + QUALITY_RULES

        for rule in all_rules:
            # Special: long function check
            if rule.get('special') == 'long_function':
                for fname, start, length in count_function_lines(content):
                    self._add(rule, rel, start, f"Fungsi '{fname}' memiliki {length} baris.")
                continue

            # Absence check (e.g. Model without $allowedFields)
            if 'check_absence' in rule:
                if re.search(rule['pattern'], content) and not re.search(rule['check_absence'], content):
                    self._add(rule, rel, 1, rule['message'])
                continue

            # Standard line-by-line pattern match
            pattern = rule.get('pattern')
            if not pattern:
                continue
            for lineno, line in enumerate(lines, 1):
                if re.search(pattern, line):
                    self._add(rule, rel, lineno, rule['message'], line.strip())

    def _add(self, rule: Dict, filepath: str, lineno: int, message: str, snippet: str = ''):
        sev = rule['severity']
        self.stats[sev] = self.stats.get(sev, 0) + 1
        self.findings.append({
            'id': rule['id'],
            'severity': sev,
            'file': filepath,
            'line': lineno,
            'name': rule['name'],
            'message': message,
            'snippet': snippet[:120] if snippet else '',
        })

    def _generate_report(self):
        print('=' * 65)
        print('HASIL CODE QUALITY CHECK')
        print('=' * 65)
        print(f"  Files  : {self.stats['files_scanned']}")
        print(f"  Lines  : {self.stats['lines_scanned']}")
        print(f"  Issues : {len(self.findings)}")
        print()

        sev_summary = []
        for s in ['CRITICAL', 'HIGH', 'MEDIUM', 'LOW', 'INFO']:
            c = self.stats.get(s, 0)
            if c:
                sev_summary.append(f"{s}: {c}")
        print('  ' + '  |  '.join(sev_summary))
        print()

        # Group by file
        by_file: Dict[str, List] = {}
        for f in sorted(self.findings, key=lambda x: (SEVERITY_ORDER.get(x['severity'], 9), x['file'], x['line'])):
            by_file.setdefault(f['file'], []).append(f)

        for filepath, issues in by_file.items():
            print(f'  {filepath}')
            for issue in issues:
                sev = issue['severity']
                col = SEVERITY_COLOR.get(sev, '')
                rst = SEVERITY_COLOR['RESET']
                icon = severity_icon(sev)
                print(f"    {col}{icon} [{issue['id']}] Line {issue['line']:>4} | {issue['message']}{rst}")
                if issue['snippet']:
                    print(f"           >> {issue['snippet']}")
            print()

        print('=' * 65)
        if self.stats.get('CRITICAL', 0) + self.stats.get('HIGH', 0) == 0:
            print('  Tidak ada issue CRITICAL/HIGH ditemukan.')
        else:
            total_critical_high = self.stats.get('CRITICAL', 0) + self.stats.get('HIGH', 0)
            print(f'  PERHATIAN: {total_critical_high} issue CRITICAL/HIGH perlu segera ditangani.')
        print('=' * 65 + '\n')


def main():
    parser = argparse.ArgumentParser(description='Code Quality Checker untuk PHP/CI4')
    parser.add_argument('target', nargs='?', default='.', help='Path project (default: current dir)')
    parser.add_argument('--verbose', '-v', action='store_true')
    parser.add_argument('--json', action='store_true', help='Output JSON')
    parser.add_argument('--output', '-o', help='Simpan output ke file')
    args = parser.parse_args()

    checker = CodeQualityChecker(args.target, verbose=args.verbose)
    results = checker.run()

    if args.json:
        output = json.dumps(results, indent=2, ensure_ascii=False)
        if args.output:
            Path(args.output).write_text(output, encoding='utf-8')
            print(f'Hasil disimpan ke {args.output}')
        else:
            print(output)


if __name__ == '__main__':
    main()
