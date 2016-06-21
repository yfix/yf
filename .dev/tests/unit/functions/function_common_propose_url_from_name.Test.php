<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';

class function_common_propose_url_from_name_test extends yf_unit_tests {
	public function test_simple() {
		$this->assertEquals('', common()->_propose_url_from_name());
		$this->assertEquals('', common()->_propose_url_from_name(false));
		$this->assertEquals('', common()->_propose_url_from_name([]));
		$this->assertEquals('', common()->_propose_url_from_name(''));
		$this->assertEquals('', common()->_propose_url_from_name('.'));
		$this->assertEquals('', common()->_propose_url_from_name('_'));
		$this->assertEquals('', common()->_propose_url_from_name(','));
		$this->assertEquals('', common()->_propose_url_from_name('/'));
		$this->assertEquals('', common()->_propose_url_from_name('~'));
		$this->assertEquals('', common()->_propose_url_from_name('!'));
		$this->assertEquals('', common()->_propose_url_from_name('@'));
		$this->assertEquals('', common()->_propose_url_from_name('#'));
		$this->assertEquals('', common()->_propose_url_from_name('%'));
		$this->assertEquals('', common()->_propose_url_from_name('^'));
		$this->assertEquals('', common()->_propose_url_from_name('&'));
		$this->assertEquals('', common()->_propose_url_from_name('*'));
		$this->assertEquals('', common()->_propose_url_from_name('('));
		$this->assertEquals('', common()->_propose_url_from_name(')'));
		$this->assertEquals('', common()->_propose_url_from_name('['));
		$this->assertEquals('', common()->_propose_url_from_name(']'));
		$this->assertEquals('', common()->_propose_url_from_name('{'));
		$this->assertEquals('', common()->_propose_url_from_name('}'));
		$this->assertEquals('', common()->_propose_url_from_name("\\"));
		$this->assertEquals('', common()->_propose_url_from_name("\t"));
		$this->assertEquals('', common()->_propose_url_from_name('|'));
		$this->assertEquals('', common()->_propose_url_from_name('"'));
		$this->assertEquals('', common()->_propose_url_from_name('\''));
		$this->assertEquals('', common()->_propose_url_from_name(':'));
		$this->assertEquals('', common()->_propose_url_from_name(';'));
		$this->assertEquals('', common()->_propose_url_from_name('<'));
		$this->assertEquals('', common()->_propose_url_from_name('>'));
		$this->assertEquals('', common()->_propose_url_from_name('+'));
		$this->assertEquals('', common()->_propose_url_from_name('?'));
		$this->assertEquals('', common()->_propose_url_from_name(PHP_EOL));
		$this->assertEquals('', common()->_propose_url_from_name(' '));
		$this->assertEquals('', common()->_propose_url_from_name('-'));
	}
	public function test_complex() {
		$this->assertEquals('', common()->_propose_url_from_name('~!@#$%^&*()_+.[](){}:;,/\'< >|/+?"'.PHP_EOL."\t\\"));
		$this->assertEquals('abcd', common()->_propose_url_from_name('~!@#$%^&*()_+.[](abcd){}:;,/\'< >|/+?"'.PHP_EOL."\t\\"));
		$this->assertEquals('', common()->_propose_url_from_name('---------'));
		$this->assertEquals('', common()->_propose_url_from_name('.........'));
		$this->assertEquals('', common()->_propose_url_from_name('.__._._.__.....'));
		$this->assertEquals('', common()->_propose_url_from_name('___.__._._.__....._'));
		$this->assertEquals('', common()->_propose_url_from_name('._-.---_._._._-_...---..'));
		$this->assertEquals('a_b', common()->_propose_url_from_name('._-.---a_._._._-_b...---..'));
		$this->assertEquals('a_b', common()->_propose_url_from_name('._-.---a_._-_--._-_--__._-_b...---..'));
		$this->assertEquals('a_b_c_d', common()->_propose_url_from_name('._-.---a_._-b_--._-c_--__._-_d...---..'));
		$this->assertEquals('a_b_c_d', common()->_propose_url_from_name('._-.---a_.._b_..,._c___.__d...---..'));
		$this->assertEquals('test', common()->_propose_url_from_name('test'));
		$this->assertEquals('test', common()->_propose_url_from_name('Test'));
		$this->assertEquals('test', common()->_propose_url_from_name('test..'));
		$this->assertEquals('t_e_s_t', common()->_propose_url_from_name('...t..e..s..t...'));
		$this->assertEquals('t_e_s_t', common()->_propose_url_from_name('___...t-e-s-t...___'));
		$this->assertEquals('test', common()->_propose_url_from_name('тест'));
		$this->assertEquals('test', common()->_propose_url_from_name('Тест'));
		$this->assertEquals('test', common()->_propose_url_from_name('__Тест__'));
		$this->assertEquals('test', common()->_propose_url_from_name('_..._Тест_..._'));
		$this->assertEquals('filtr_junior_vauen_pach_10_sht', common()->_propose_url_from_name('ФИЛЬТР JUNIOR VAUEN (пач/10 шт)'));
		$this->assertEquals('filtr_junior_vauen_pach_10_sht', common()->_propose_url_from_name('_._._ФИЛЬТР JUNIOR VAUEN (пач/10 шт)_..__...'));
		$this->assertEquals('filtr_junior_vauen_pach_10_sht', common()->_propose_url_from_name('_._._  ФИЛЬТР ___  JUNIOR  ...  VAUEN   (пач/10 шт)  _..__...'));
		$this->assertEquals('folga_dlya_kalyana_al_fakher_pach35', common()->_propose_url_from_name('ФОЛЬГА ДЛЯ  КАЛЬЯНА AL FAKHER Пач35'));
	}
	public function test_dashes() {
		$this->assertEquals('a_b_c_d', common()->_propose_url_from_name('._-.---a_.._b_..,._c___.__d...---..'));
		$this->assertEquals('a_b_c_d', common()->_propose_url_from_name('._-.---a_.._b_..,._c___.__d...---..', '', $force_dashes = true));

		$this->assertEquals('t_e_s_t', common()->_propose_url_from_name('...t..e..s..t...'));
		$this->assertEquals('t_e_s_t', common()->_propose_url_from_name('...t..e..s..t...', '', $force_dashes = true));

		$this->assertEquals('filtr_junior_vauen_pach_10_sht', common()->_propose_url_from_name('_._._  ФИЛЬТР ___  JUNIOR  ...  VAUEN   (пач/10 шт)  _..__...'));
		$this->assertEquals('filtr_junior_vauen_pach_10_sht', common()->_propose_url_from_name('_._._  ФИЛЬТР ___  JUNIOR  ...  VAUEN   (пач/10 шт)  _..__...', '', $force_dashes = true));
	}
	public function test_force_dashes() {
		$this->assertEquals('sautgempton', common()->_propose_url_from_name('Саутгемптон'));
		$this->assertEquals('tottenhem', common()->_propose_url_from_name('Тоттенхэм'));
		$this->assertEquals('sautgempton_tottenhem', common()->_propose_url_from_name('Саутгемптон-Тоттенхэм'));
		$this->assertEquals('sautgempton-tottenhem', common()->_propose_url_from_name('Саутгемптон-Тоттенхэм', $dashes = true));
		$this->assertEquals('sautgempton_tottenhem', common()->_propose_url_from_name('Саутгемптон-Тоттенхэм', $dashes = false));
		$this->assertEquals('sautgempton-tottenhem', common()->_propose_url_from_name('Саутгемптон - Тоттенхэм', $dashes = true));
		$this->assertEquals('sautgempton_tottenhem', common()->_propose_url_from_name('Саутгемптон - Тоттенхэм', $dashes = false));
		$this->assertEquals('sautgempton-tottenhem', common()->_propose_url_from_name('Саутгемптон   ₋   Тоттенхэм', $dashes = true));
		$this->assertEquals('sautgempton_tottenhem', common()->_propose_url_from_name('Саутгемптон   ₋   Тоттенхэм', $dashes = false));
		$this->assertEquals('filtr_junior_vauen_pach_10_sht', common()->_propose_url_from_name('ФИЛЬТР JUNIOR VAUEN (пач/10 шт)'));
		$this->assertEquals('filtr-junior-vauen-pach-10-sht', common()->_propose_url_from_name('ФИЛЬТР JUNIOR VAUEN (пач/10 шт)', $dashes = true));
		$this->assertEquals('filtr_junior_vauen_pach_10_sht', common()->_propose_url_from_name('_._._ФИЛЬТР JUNIOR VAUEN (пач/10 шт)_..__...'));
		$this->assertEquals('filtr-junior-vauen-pach-10-sht', common()->_propose_url_from_name('_._._ФИЛЬТР JUNIOR VAUEN (пач/10 шт)_..__...', $dashes = true));
		$this->assertEquals('filtr_junior_vauen_pach_10_sht', common()->_propose_url_from_name('_._._  ФИЛЬТР ___  JUNIOR  ...  VAUEN   (пач/10 шт)  _..__...'));
		$this->assertEquals('filtr-junior-vauen-pach-10-sht', common()->_propose_url_from_name('_._._  ФИЛЬТР ___  JUNIOR  ...  VAUEN   (пач/10 шт)  _..__...', $dashes = true));
		$this->assertEquals('folga_dlya_kalyana_al_fakher_pach35', common()->_propose_url_from_name('ФОЛЬГА ДЛЯ  КАЛЬЯНА AL FAKHER Пач35'));
		$this->assertEquals('folga-dlya-kalyana-al-fakher-pach35', common()->_propose_url_from_name('ФОЛЬГА ДЛЯ  КАЛЬЯНА AL FAKHER Пач35', $dashes = true));
	}
}