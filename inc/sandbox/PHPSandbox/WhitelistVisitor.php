<?php
    namespace PHPSandbox;

    class WhitelistVisitor extends \PHPParser_NodeVisitorAbstract {
        /**
         * @var PHPSandbox
         */
        protected $sandbox;

        public function __construct(PHPSandbox $sandbox){
            $this->sandbox = $sandbox;
        }

        public function leaveNode(\PHPParser_Node $node){
            if($node instanceof \PHPParser_Node_Expr_FuncCall && $node->name instanceof \PHPParser_Node_Name){
                $this->sandbox->whitelist_func($node->name->toString());
            } else if($node instanceof \PHPParser_Node_Stmt_Function && is_string($node->name) && $node->name){
                $this->sandbox->whitelist_func($node->name);
            } else if(($node instanceof \PHPParser_Node_Expr_Variable || $node instanceof \PHPParser_Node_Stmt_StaticVar) && is_string($node->name) && $this->sandbox->has_whitelist_vars()){
                $this->sandbox->whitelist_var($node->name);
            } else if($node instanceof \PHPParser_Node_Expr_FuncCall && $node->name instanceof \PHPParser_Node_Name && $node->name->toString() == 'define' && !$this->sandbox->is_defined_func('define')){
                $name = isset($node->args[0]) ? $node->args[0] : null;
                if($name && $name instanceof \PHPParser_Node_Arg && $name->value instanceof \PHPParser_Node_Scalar_String && is_string($name->value->value) && $name->value->value){
                    $this->sandbox->whitelist_const($name->value->value);
                }
            } else if($node instanceof \PHPParser_Node_Expr_ConstFetch && $node->name instanceof \PHPParser_Node_Name){
                $this->sandbox->whitelist_const($node->name->toString());
            } else if($node instanceof \PHPParser_Node_Stmt_Class && is_string($node->name)){
                $this->sandbox->whitelist_class($node->name);
                $this->sandbox->whitelist_type($node->name);
            } else if($node instanceof \PHPParser_Node_Expr_New && $node->class instanceof \PHPParser_Node_Name){
                $this->sandbox->whitelist_type($node->class->toString());
            } else if($node instanceof \PHPParser_Node_Stmt_Global && $this->sandbox->has_whitelist_vars()){
                foreach($node->vars as $var){
                    /**
                     * @var \PHPParser_Node_Expr_Variable    $var
                     */
                    if($var instanceof \PHPParser_Node_Expr_Variable){
                        $this->sandbox->whitelist_var($var->name);
                    }
                }
            } else if($node instanceof \PHPParser_Node_Stmt_Namespace){
                if($node->name instanceof \PHPParser_Node_Name){
                    $this->sandbox->define_namespace($node->name->toString());
                }
                return false;
            } else if($node instanceof \PHPParser_Node_Stmt_Use){
                foreach($node->uses as $use){
                    /**
                     * @var \PHPParser_Node_Stmt_UseUse    $use
                     */
                    if($use instanceof \PHPParser_Node_Stmt_UseUse && $use->name instanceof \PHPParser_Node_Name && (is_string($use->alias) || is_null($use->alias))){
                        $this->sandbox->define_alias($use->name->toString(), $use->alias);
                    }
                }
                return false;
            }
        }
    }