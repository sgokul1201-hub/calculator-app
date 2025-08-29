<?php
// api.php
session_start();
header('Content-Type: application/json');

if (!isset($_POST['action'])) {
    exit(json_encode(['success'=>false,'error'=>'No action']));
}

$action = $_POST['action'];

// initialize memory & history
if (!isset($_SESSION['calc_memory'])) $_SESSION['calc_memory'] = 0.0;
if (!isset($_SESSION['calc_history'])) $_SESSION['calc_history'] = [];

function respond($arr){ echo json_encode($arr); exit; }

/**
 * Tokenize and evaluate arithmetic expressions safely.
 * Supports: + - * / ^ ( ) decimals, sqrt (unary as √ or function), % (as unary percent), and unary +/-.
 */
function evaluate_expression($expr) {
    // normalize and replace unicode chars
    $expr = str_replace(['×','÷','−','√'], ['*','/','-','sqrt'], $expr);
    // remove spaces
    $expr = trim($expr);

    // allowed tokens regex (digits, dot, operators, parentheses, sqrt, percent)
    // We'll tokenize by pattern
    $pattern = '/(\d+(\.\d+)?)|sqrt|[\+\-\*\/\^\%\(\)]/i';
    preg_match_all($pattern, $expr, $matches);
    $tokens = $matches[0];

    if (empty($tokens)) throw new Exception("Empty expression");

    // convert to RPN using shunting-yard
    $outputQueue = [];
    $opStack = [];

    $precedence = ['+' => 2, '-' => 2, '*' => 3, '/' => 3, '^' => 4, '%' => 5];
    $rightAssoc = ['^' => true];

    $i = 0;
    $prevToken = null;
    while ($i < count($tokens)) {
        $token = $tokens[$i];
        // number
        if (is_numeric($token)) {
            $outputQueue[] = $token + 0; // numeric
        } elseif (strcasecmp($token,'sqrt') === 0) {
            // treat sqrt as function (unary)
            array_push($opStack, 'sqrt');
        } elseif ($token === '%') {
            // percent is unary postfix operator with highest precedence
            // We'll push it as operator and evaluate later
            while (!empty($opStack)) {
                $top = end($opStack);
                if ($top === '(') break;
                $outputQueue[] = array_pop($opStack);
            }
            // push percent operator marker — but to keep evaluation predictable, push '%' into output as unary
            $outputQueue[] = '%';
        } elseif (in_array($token, ['+','-','*','/','^'])) {
            // handle unary plus/minus:
            if (($prevToken === null) || ($prevToken === '(') || in_array($prevToken, ['+','-','*','/','^'])) {
                // unary
                if ($token === '+') {
                    // unary plus — ignore
                } else {
                    // unary minus => treat as 0 - number: push 0 then unary minus as binary
                    $outputQueue[] = 0;
                    // fallthrough to operator handling to push '-'
                    // continue as normal
                }
            }
            // operator precedence
            while (!empty($opStack)) {
                $o2 = end($opStack);
                if ($o2 === '(') break;
                $p1 = $precedence[$token];
                $p2 = isset($precedence[$o2]) ? $precedence[$o2] : 0;
                if ((!isset($rightAssoc[$token]) && $p1 <= $p2) || (isset($rightAssoc[$token]) && $p1 < $p2)) {
                    $outputQueue[] = array_pop($opStack);
                } else break;
            }
            array_push($opStack, $token);
        } elseif ($token === '(') {
            array_push($opStack, $token);
        } elseif ($token === ')') {
            // pop until '('
            $found = false;
            while (!empty($opStack)) {
                $top = array_pop($opStack);
                if ($top === '(') { $found = true; break; }
                $outputQueue[] = $top;
            }
            if (!$found) throw new Exception("Mismatched parentheses");
            // if top of stack is function like sqrt, pop it to output
            if (!empty($opStack)) {
                $top = end($opStack);
                if (strcasecmp($top,'sqrt') === 0) {
                    $outputQueue[] = array_pop($opStack);
                }
            }
        } else {
            throw new Exception("Invalid token: " . $token);
        }

        $prevToken = $token;
        $i++;
    }

    while (!empty($opStack)) {
        $op = array_pop($opStack);
        if ($op === '(' || $op === ')') throw new Exception("Mismatched parentheses");
        $outputQueue[] = $op;
    }

    // Evaluate RPN
    $stack = [];
    foreach ($outputQueue as $t) {
        if (is_numeric($t)) {
            array_push($stack, (float)$t);
        } elseif ($t === '%') {
            // percent unary: pop x => push x/100
            if (count($stack) < 1) throw new Exception("Percent operator error");
            $v = array_pop($stack);
            array_push($stack, $v / 100.0);
        } elseif (strcasecmp($t,'sqrt') === 0) {
            if (count($stack) < 1) throw new Exception("Sqrt error");
            $v = array_pop($stack);
            if ($v < 0) throw new Exception("Square root of negative");
            array_push($stack, sqrt($v));
        } elseif (in_array($t, ['+','-','*','/','^'])) {
            if (count($stack) < 2) throw new Exception("Operator ".$t." needs two operands");
            $b = array_pop($stack);
            $a = array_pop($stack);
            switch ($t) {
                case '+': $r = $a + $b; break;
                case '-': $r = $a - $b; break;
                case '*': $r = $a * $b; break;
                case '/':
                    if ($b == 0) throw new Exception("Division by zero");
                    $r = $a / $b; break;
                case '^': $r = pow($a, $b); break;
                default: throw new Exception("Unknown op");
            }
            array_push($stack, $r);
        } else {
            throw new Exception("Unexpected RPN token ".$t);
        }
    }

    if (count($stack) !== 1) throw new Exception("Invalid expression");
    // round to reasonable precision
    return round($stack[0], 12) + 0;
}

// route actions
try {
    if ($action === 'compute') {
        $expr = isset($_POST['expr']) ? (string)$_POST['expr'] : '';
        if (strlen($expr) > 200) respond(['success'=>false,'error'=>'Expression too long']);
        // basic sanitize: allowed characters digits, . + - * / ^ % ( ) and sqrt string and spaces
        if (preg_match('/[^0-9\.\+\-\*\/\^\%\(\)sqrta-zA-Z ]/u', $expr)) {
            // allow only letters for sqrt
            // Actually re-check tokens in evaluator will catch invalid tokens
        }
        $result = evaluate_expression($expr);
        // store in history
        $entry = ['expr'=>$expr,'result'=>$result,'time'=>date('Y-m-d H:i:s')];
        array_unshift($_SESSION['calc_history'], $entry);
        // cap history length
        $_SESSION['calc_history'] = array_slice($_SESSION['calc_history'], 0, 100);
        respond(['success'=>true,'result'=>$result,'entry'=>$entry,'history'=>$_SESSION['calc_history']]);
    }

    elseif ($action === 'memory') {
        $op = isset($_POST['op']) ? $_POST['op'] : '';
        // MR, MC, M+, M-
        if ($op === 'MC') {
            $_SESSION['calc_memory'] = 0.0;
            respond(['success'=>true,'memory'=>$_SESSION['calc_memory']]);
        } elseif ($op === 'MR') {
            respond(['success'=>true,'memory'=>$_SESSION['calc_memory']]);
        } elseif (in_array($op, ['M+','M-'])) {
            $val = isset($_POST['val']) ? floatval($_POST['val']) : 0.0;
            if ($op === 'M+') $_SESSION['calc_memory'] += $val;
            else $_SESSION['calc_memory'] -= $val;
            respond(['success'=>true,'memory'=>$_SESSION['calc_memory']]);
        } else {
            respond(['success'=>false,'error'=>'Unknown memory op']);
        }
    }

    elseif ($action === 'history') {
        // return history or clear
        $do = isset($_POST['do']) ? $_POST['do'] : 'get';
        if ($do === 'get') {
            respond(['success'=>true,'history'=>$_SESSION['calc_history']]);
        } elseif ($do === 'clear') {
            $_SESSION['calc_history'] = [];
            respond(['success'=>true,'history'=>[]]);
        } else respond(['success'=>false,'error'=>'Unknown history action']);
    }

    else {
        respond(['success'=>false,'error'=>'Unknown action']);
    }
} catch (Exception $e) {
    respond(['success'=>false,'error'=>$e->getMessage()]);
}
