<?php
fpErrorNotifier::setInstance(new fpErrorNotifier($this->getEventDispatcher()));
fpErrorNotifier::getInstance()->handler()->initialize();